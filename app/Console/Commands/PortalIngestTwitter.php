<?php

namespace App\Console\Commands;

use App\Services\Portal\XClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PortalIngestTwitter extends Command
{
    protected $signature = 'portal:ingest-twitter
        {--dry-run : Do not write to DB}
        {--max-results= : Override max results (10-100)}
        {--reset-since : Ignore stored since_id once (incremental mode)}
        {--start= : ISO8601 start_time (backfill mode, requires search/all)}
        {--end= : ISO8601 end_time (backfill mode, requires search/all)}
        {--pages=1 : Number of pages to fetch (each page = 1 billable search request)}
        {--no-state : Do not update stored since_id (recommended for backfills)}
    ';

    protected $description = 'Ingest portal posts from X into portal_raw_posts (supports backfill via start/end + pagination)';

    public function handle(XClient $x): int
    {
        $dryRun = (bool)($this->option('dry-run') || config('services.x.dry_run'));
        $maxResults = (int)($this->option('max-results') ?: config('services.x.max_results', 25));
        $maxResults = max(10, min(100, $maxResults));

        $pages = (int)$this->option('pages');
        $pages = max(1, min(200, $pages)); // safety clamp

        $start = $this->option('start');
        $end   = $this->option('end');
        $isBackfill = !empty($start) || !empty($end);

        if ($isBackfill) {
            // Require both, to avoid accidental huge archive pulls
            if (empty($start) || empty($end)) {
                $this->error("Backfill requires BOTH --start and --end (ISO8601, e.g. 2025-03-01T00:00:00Z).");
                return self::FAILURE;
            }

            // Full archive endpoint required for historical
            $endpoint = (string)config('services.x.search_endpoint', 'recent');
            if ($endpoint !== 'all') {
                $this->warn("You are in backfill mode but X_SEARCH_ENDPOINT is '{$endpoint}'. Set X_SEARCH_ENDPOINT=all and rerun.");
                return self::FAILURE;
            }

            // Validate ISO8601
            try {
                Carbon::parse($start);
                Carbon::parse($end);
            } catch (\Throwable $e) {
                $this->error("Invalid --start/--end. Use ISO8601 like 2025-03-01T00:00:00Z");
                return self::FAILURE;
            }
        }

        // ---- Daily request cap guard (still applies) ----
        $todayKey = 'x_requests_' . now()->toDateString();
        $used = (int)($this->stateGet($todayKey) ?? 0);
        $cap = (int)config('services.x.daily_request_cap', 25);

        // If you’re backfilling, you might WANT more than the default cap.
        // We’ll still enforce it unless you bump X_DAILY_REQUEST_CAP.
        if ($used >= $cap) {
            $this->warn("Daily request cap reached ({$used}/{$cap}). Skipping.");
            return self::SUCCESS;
        }

        $sinceKey = 'x_since_id_portal';
        $sinceId = null;

        if (!$isBackfill) {
            $sinceId = $this->option('reset-since') ? null : $this->stateGet($sinceKey);
        }

        $query = $this->portalQuery();

        $this->line("dry_run=" . ($dryRun ? 'true' : 'false')
            . " max_results={$maxResults}"
            . " pages={$pages}"
            . " mode=" . ($isBackfill ? 'backfill' : 'incremental')
        );

        if ($isBackfill) {
            $this->line("start={$start} end={$end}");
        } else {
            $this->line("since_id=" . ($sinceId ?: 'none'));
        }

        $this->line("query={$query}");

        $totalReturned = 0;
        $totalIngested = 0;

        $nextToken = null;
        $newestIdSeen = null;

        for ($page = 1; $page <= $pages; $page++) {
            // cap check each loop
            $used = (int)($this->stateGet($todayKey) ?? 0);
            if ($used >= $cap) {
                $this->warn("Daily request cap reached mid-run ({$used}/{$cap}). Stopping pagination.");
                break;
            }

            $params = [
                'query' => $query,
                'max_results' => $maxResults,
                'tweet.fields' => 'created_at,author_id',
                'expansions' => 'author_id',
                'user.fields' => 'username',
            ];

            if ($isBackfill) {
                $params['start_time'] = $start;
                $params['end_time'] = $end;
            } else {
                if ($sinceId) {
                    $params['since_id'] = $sinceId;
                }
            }

            if ($nextToken) {
                $params['next_token'] = $nextToken;
            }

            // ---- Call X API (1 billable request per page) ----
            $json = $x->search($params);

            // bump usage counter
            $this->stateSet($todayKey, (string)($used + 1));

            $tweets = $json['data'] ?? [];
            $meta = $json['meta'] ?? [];

            $returned = count($tweets);
            $totalReturned += $returned;

            if ($returned === 0) {
                $this->info("page {$page}: 0 tweets returned. Stopping.");
                break;
            }

            // Map author_id => username
            $users = $json['includes']['users'] ?? [];
            $authorMap = [];
            foreach ($users as $u) {
                if (!empty($u['id']) && !empty($u['username'])) {
                    $authorMap[$u['id']] = $u['username'];
                }
            }

            $ingestedThisPage = 0;

            foreach ($tweets as $t) {
                $tweetId = (string)($t['id'] ?? '');
                $text = (string)($t['text'] ?? '');
                $createdAt = (string)($t['created_at'] ?? '');
                $authorId = (string)($t['author_id'] ?? '');

                if (!$tweetId || !$text || !$createdAt) {
                    continue;
                }

                $username = $authorMap[$authorId] ?? null;
                $permalink = $username ? ("https://x.com/{$username}/status/{$tweetId}") : null;

                if (!$newestIdSeen || strcmp($tweetId, $newestIdSeen) > 0) {
                    $newestIdSeen = $tweetId;
                }

                if ($dryRun) {
                    $this->line("[DRY] {$tweetId} @{$username} {$createdAt} " . mb_substr($text, 0, 110));
                    $ingestedThisPage++;
                    continue;
                }

                if (!$username) {
                    continue;
                }

                // Match source_id by handle == username (portal_sources.handle has no @)
                $sourceId = DB::table('portal_sources')->where('handle', $username)->value('id');
                if (!$sourceId) {
                    // Not in your source list; ignore
                    continue;
                }

                DB::table('portal_raw_posts')->updateOrInsert(
                    ['external_post_id' => $tweetId],
                    [
                        'source_id' => $sourceId,
                        'posted_at' => Carbon::parse($createdAt)->toDateTimeString(),
                        'text' => $text,
                        'permalink' => $permalink,
                        'payload' => json_encode($t),
                        'parse_status' => 'unparsed',
                        'parsed_at' => null,
                        'parse_error' => null,
                        'event_id' => null,
                        'from_team' => null,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );

                $ingestedThisPage++;
            }

            $totalIngested += $ingestedThisPage;

            $nextToken = $meta['next_token'] ?? null;

            $this->info("page {$page}: tweets_returned={$returned} ingested={$ingestedThisPage}" . ($nextToken ? " next_token=yes" : " next_token=no"));

            // No more pages available from API
            if (!$nextToken) {
                break;
            }
        }

        // Only advance since_id in incremental mode, and only if not dry-run, and unless --no-state
        $noState = (bool)$this->option('no-state');
        if (!$dryRun && !$noState && !$isBackfill && $newestIdSeen) {
            $this->stateSet($sinceKey, $newestIdSeen);
        }

        $this->info("TOTAL: tweets_returned={$totalReturned} ingested={$totalIngested}" . ($newestIdSeen ? " newest_id_seen={$newestIdSeen}" : ""));

        return self::SUCCESS;
    }

    private function portalQuery(): string
    {
        return '(from:VerbalCommits OR from:portal_updates OR from:ThePortalReport OR from:JeffBorzello OR from:GoodmanHoops OR from:TravisBranham_ OR from:TrillyDonovan OR from:MikeSautter_ OR from:TheFieldOf68 OR from:AaronTorresPod OR from:TransferPortal) ' .
            '("entered the transfer portal" OR "entering the transfer portal" OR "enter the transfer portal" OR "hit the transfer portal" OR "hit the portal") ' .
            '-football -qb -wr -rb -te -ol -dl -baseball -track';
    }

    private function stateGet(string $key): ?string
    {
        return DB::table('portal_ingest_state')->where('key', $key)->value('value');
    }

    private function stateSet(string $key, string $value): void
    {
        DB::table('portal_ingest_state')->updateOrInsert(
            ['key' => $key],
            ['value' => $value, 'updated_at' => now(), 'created_at' => now()]
        );
    }
}