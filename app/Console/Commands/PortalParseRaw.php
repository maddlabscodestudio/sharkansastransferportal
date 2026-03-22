<?php

namespace App\Console\Commands;

use App\Services\Portal\PortalEventService;
use App\Services\Portal\PortalPostParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PortalParseRaw extends Command
{
    protected $signature = 'portal:parse-raw {--limit=5000}';
    protected $description = 'Parse portal_raw_posts into canonical portal_events (loose mode with confidence)';

    public function handle(): int
    {
        $parser = new PortalPostParser();
        $events = new PortalEventService();

        $rows = DB::table('portal_raw_posts as rp')
            ->join('portal_sources as s', 's.id', '=', 'rp.source_id')
            ->where('rp.parse_status', 'unparsed')
            ->select('rp.*', 's.handle', 's.needs_sport_filter', 's.tier')
            ->orderBy('rp.posted_at')
            ->limit((int)$this->option('limit'))
            ->get();

        $parsed = 0; $skipped = 0; $failed = 0;

        foreach ($rows as $rp) {
            try {
                
                $text = html_entity_decode($rp->text, ENT_QUOTES | ENT_HTML5);
                $res = $parser->parseEntered($text, (bool)$rp->needs_sport_filter);

                if (!$res || empty($res['player_name'])) {
                    DB::table('portal_raw_posts')->where('id', $rp->id)->update([
                        'parse_status' => 'skipped',
                        'parsed_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $skipped++;
                    continue;
                }

                // (optional but useful) persist parsed team onto the raw post for debugging/audit
                DB::table('portal_raw_posts')->where('id', $rp->id)->update([
                    'from_team' => $res['from_team'] ?? null,
                    'updated_at' => now(),
                ]);

                $eventId = $events->attachOrCreateEvent([
                    'player_name' => $res['player_name'],
                    'from_team'   => $res['from_team'] ?? null,
                    'status'      => 'entered',
                    'posted_at'   => $rp->posted_at,
                    'raw_post_id' => $rp->id,
                    'needs_waiver' => $res['needs_waiver'] ?? false,
                    'no_eligibility_remaining' => $res['no_eligibility_remaining'] ?? false,
                    'eligibility_note' => $res['eligibility_note'] ?? null,
                ]);


                // Recompute confidence + report_count based on UNIQUE sources attached to this event
                $stats = DB::table('portal_raw_posts as rp2')
                    ->join('portal_sources as s2', 's2.id', '=', 'rp2.source_id')
                    ->where('rp2.event_id', $eventId)
                    ->selectRaw('
                        COUNT(DISTINCT s2.id) as source_count,
                        MIN(s2.tier) as best_tier
                    ')
                    ->first();

                $confidence = 'low';

                if (($stats->best_tier ?? 999) <= 1 || ($stats->source_count ?? 0) >= 2) {
                    $confidence = 'high';
                } elseif (($stats->best_tier ?? 999) == 2) {
                    $confidence = 'med';
                }

                DB::table('portal_events')->where('id', $eventId)->update([
                    'confidence' => $confidence,
                    'report_count' => (int) ($stats->source_count ?? 1), // make report_count = unique sources
                    'updated_at' => now(),
                ]);

                DB::table('portal_raw_posts')->where('id', $rp->id)->update([
                    'parse_status' => 'parsed',
                    'parsed_at' => now(),
                    'updated_at' => now(),
                ]);

                $parsed++;
            } catch (\Throwable $e) {
                DB::table('portal_raw_posts')->where('id', $rp->id)->update([
                    'parse_status' => 'failed',
                    'parse_error' => $e->getMessage(),
                    'parsed_at' => now(),
                    'updated_at' => now(),
                ]);
                $failed++;
            }
        }

        $this->info("parsed={$parsed}, skipped={$skipped}, failed={$failed}");
        return self::SUCCESS;
    }
}