<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\SportsDataIO\SportsDataIoClient;
use App\Models\PlayerSeasonStat;
use Carbon\Carbon;

class PortalSyncMissingStats extends Command
{
    protected $signature = 'portal:sync-missing-stats {season=2026} {--limit=50}';
    protected $description = 'Sync stats for portal players missing stats';

    public function handle(SportsDataIoClient $client): int
    {
        $season = (int) $this->argument('season');
        $limit = (int) $this->option('limit');

        $this->info("Syncing missing stats for season {$season}...");

        // Get distinct players from portal_events that don't have stats yet
        $players = DB::table('portal_events as e')
        ->leftJoin('player_season_stats as s', function ($join) use ($season) {
            $join->on('e.player_name', '=', 's.player_name')
                ->where('s.season', '=', $season);
        })
        ->whereNull('s.id')
        ->select('e.player_name', 'e.from_team', DB::raw('MAX(e.first_reported_at) as latest_reported_at'))
        ->groupBy('e.player_name', 'e.from_team')
        ->orderByDesc('latest_reported_at')
        ->limit($limit)
        ->get();

        if ($players->isEmpty()) {
            $this->info('No missing players found.');
            return self::SUCCESS;
        }

        $this->info("Found {$players->count()} players missing stats.");

        // Pull API data once
        $rows = collect($client->get("/v3/cbb/stats/json/PlayerSeasonStats/{$season}"));
        $teams = collect($client->get('/v3/cbb/scores/json/teams'));

        foreach ($players as $p) {
            $player = trim($p->player_name);
            $team = trim($p->from_team ?? '');

            $this->line("Processing: {$player} ({$team})");

            $normalizedPlayer = $this->normalize($player);
            $normalizedTeam = $this->normalize($team);

            // Map team → API key
            $matchedTeam = $teams->first(function ($teamRow) use ($normalizedTeam) {
                $school = $this->normalize($teamRow['School'] ?? '');
                $name = $this->normalize($teamRow['Name'] ?? '');
                $key = $this->normalize($teamRow['Key'] ?? '');

                return $school === $normalizedTeam
                    || $name === $normalizedTeam
                    || $key === $normalizedTeam;
            });

            $teamKey = $matchedTeam['Key'] ?? null;

            // Match player
            $match = $rows->first(function ($row) use ($normalizedPlayer, $teamKey) {
                $rowName = $this->normalize($row['Name'] ?? '');
                $rowTeam = strtoupper(trim((string) ($row['Team'] ?? '')));

                $nameMatch = $rowName === $normalizedPlayer;
                $teamMatch = $teamKey ? $rowTeam === strtoupper($teamKey) : true;

                return $nameMatch && $teamMatch;
            });

            if (!$match) {
                $this->warn("No match for {$player}");
                continue;
            }

            PlayerSeasonStat::updateOrCreate(
                [
                    'sportsdataio_player_id' => $match['PlayerID'],
                    'season' => $match['Season'],
                    'season_type' => $match['SeasonType'] ?? null,
                ],
                [
                    'player_name' => $match['Name'] ?? null,
                    'team_key' => $match['Team'] ?? null,
                    'team_name' => $team,
                    'sportsdataio_team_id' => $match['TeamID'] ?? null,
                    'sportsdataio_stat_id' => $match['StatID'] ?? null,
                    'position' => $match['Position'] ?? null,
                    'games' => $match['Games'] ?? null,
                    'minutes' => $match['Minutes'] ?? null,
                    'points' => $match['Points'] ?? null,
                    'rebounds' => $match['Rebounds'] ?? null,
                    'assists' => $match['Assists'] ?? null,
                    'steals' => $match['Steals'] ?? null,
                    'blocked_shots' => $match['BlockedShots'] ?? null,
                    'turnovers' => $match['Turnovers'] ?? null,
                    'field_goals_percentage' => $match['FieldGoalsPercentage'] ?? null,
                    'three_pointers_percentage' => $match['ThreePointersPercentage'] ?? null,
                    'free_throws_percentage' => $match['FreeThrowsPercentage'] ?? null,
                    'true_shooting_percentage' => $match['TrueShootingPercentage'] ?? null,
                    'player_efficiency_rating' => $match['PlayerEfficiencyRating'] ?? null,
                    'usage_rate_percentage' => $match['UsageRatePercentage'] ?? null,
                    'source_updated_at' => !empty($match['Updated']) ? Carbon::parse($match['Updated']) : null,
                    'synced_at' => now(),
                    'raw_payload' => $match,
                ]
            );

            $this->info("Saved stats for {$player}");
        }

        return self::SUCCESS;
    }

    private function normalize(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value);
        return trim($value);
    }
}