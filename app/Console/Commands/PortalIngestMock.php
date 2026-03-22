<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PortalIngestMock extends Command
{
    protected $signature = 'portal:ingest-mock
        {--per-source=40 : Number of raw posts per source}
        {--start=2025-03-01 : Start date (YYYY-MM-DD)}
        {--end=2025-05-31 : End date (YYYY-MM-DD)}';

    protected $description = 'Generate mock portal raw posts (Mar–May 2025) and parse "entered portal" events';

    public function handle(): int
    {
        $perSource = (int) $this->option('per-source');
        $start = Carbon::parse($this->option('start'), 'UTC')->startOfDay();
        $end = Carbon::parse($this->option('end'), 'UTC')->endOfDay();

        $sources = DB::table('portal_sources')->where('is_enabled', 1)->get();

        if ($sources->isEmpty()) {
            $this->error('No portal_sources found. Run: php artisan db:seed --class=PortalSourcesSeeder');
            return self::FAILURE;
        }

        $names = [
            'John Doe','Jordan Smith','Jaylen Carter','Darius Johnson','Marcus Green',
            'Tyrese Brown','Cam Williams','Malik Thompson','Noah Jackson','Ethan Miller'
        ];
        $teams = ['Arkansas','Kansas','Duke','Auburn','Texas','Houston','Baylor','Kentucky','Gonzaga','Alabama'];

        $insertedRaw = 0;
        $insertedEntries = 0;

        foreach ($sources as $source) {
            for ($i = 0; $i < $perSource; $i++) {
                $postedAt = Carbon::createFromTimestamp(rand($start->timestamp, $end->timestamp), 'UTC');

                $player = $names[array_rand($names)];
                $from = $teams[array_rand($teams)];

                // 80% are portal-entry style, 20% are noise (to test filtering later)
                $isEntry = rand(1, 100) <= 80;

                $text = $isEntry
                    ? "{$from} {$this->randPositionWord()} {$player} has entered the transfer portal."
                    : $this->randomNoiseText($player, $from);

                $externalId = 'mock_' . $source->handle . '_' . Str::uuid()->toString();
                $permalink = 'https://x.com/' . $source->handle . '/status/' . Str::random(18);

                // Insert raw post (dedupe by external_post_id)
                DB::table('portal_raw_posts')->insert([
                    'source_id' => $source->id,
                    'external_post_id' => $externalId,
                    'posted_at' => $postedAt,
                    'text' => $text,
                    'permalink' => $permalink,
                    'payload' => json_encode(['mock' => true]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $insertedRaw++;

                if ($isEntry) {
                    // Parse player name (mock uses fixed pattern)
                    // In real parsing, we'll do regex + confidence scoring.
                    $rawPostId = DB::getPdo()->lastInsertId();

                    DB::table('portal_entries')->insertOrIgnore([
                        'raw_post_id' => $rawPostId,
                        'player_name' => $player,
                        'from_team' => $from,
                        'status' => 'entered',
                        'source' => '@' . $source->handle,
                        'posted_at' => $postedAt,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $insertedEntries++;
                }
            }
        }

        $this->info("Inserted raw posts: {$insertedRaw}");
        $this->info("Inserted portal entries: {$insertedEntries}");
        $this->info("Test range: {$start->toDateString()} → {$end->toDateString()} (UTC)");

        return self::SUCCESS;
    }

    private function randPositionWord(): string
    {
        return collect(['guard', 'wing', 'forward', 'center'])->random();
    }

    private function randomNoiseText(string $player, string $team): string
    {
        $noise = [
            "{$player} is visiting {$team} this weekend.",
            "Rumor: {$player} could be on the move soon.",
            "{$team} needs shooting badly.",
            "Hearing {$player} might consider the portal.",
        ];
        return $noise[array_rand($noise)];
    }
}