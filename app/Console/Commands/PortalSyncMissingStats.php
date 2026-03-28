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

        // Get distinct players from portal_events whose stats row is missing or incomplete
        $players = DB::table('portal_events as e')
            ->leftJoin('player_season_stats as s', function ($join) use ($season) {
                $join->on('e.player_name', '=', 's.player_name')
                    ->where('s.season', '=', $season);
            })
            ->where(function ($q) {
                $q->whereNull('s.id')
                ->orWhereNull('s.field_goals_made')
                ->orWhereNull('s.field_goals_attempted')
                ->orWhereNull('s.effective_field_goals_percentage');
            })
            ->select(
                'e.player_name',
                'e.from_team',
                DB::raw('MAX(e.first_reported_at) as latest_reported_at')
            )
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
            $match = $this->findPlayerMatch($rows, $player, $teamKey);

            if (!$match) {
                $this->warn("No match for {$player} ({$team})");
                continue;
            }

            PlayerSeasonStat::updateOrCreate(
                [
                    'sportsdataio_player_id' => $match['PlayerID'],
                    'season' => $match['Season'],
                    'season_type' => $match['SeasonType'] ?? null,
                ],
                [
                    'player_name' => $player,
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

                    'field_goals_made' => $match['FieldGoalsMade'] ?? null,
                    'field_goals_attempted' => $match['FieldGoalsAttempted'] ?? null,
                    'two_pointers_made' => $match['TwoPointersMade'] ?? null,
                    'two_pointers_attempted' => $match['TwoPointersAttempted'] ?? null,
                    'two_pointers_percentage' => $match['TwoPointersPercentage'] ?? null,
                    'three_pointers_made' => $match['ThreePointersMade'] ?? null,
                    'three_pointers_attempted' => $match['ThreePointersAttempted'] ?? null,
                    'free_throws_made' => $match['FreeThrowsMade'] ?? null,
                    'free_throws_attempted' => $match['FreeThrowsAttempted'] ?? null,
                    'offensive_rebounds' => $match['OffensiveRebounds'] ?? null,
                    'defensive_rebounds' => $match['DefensiveRebounds'] ?? null,
                    'personal_fouls' => $match['PersonalFouls'] ?? null,

                    'effective_field_goals_percentage' => $match['EffectiveFieldGoalsPercentage'] ?? null,
                    'offensive_rebounds_percentage' => $match['OffensiveReboundsPercentage'] ?? null,
                    'defensive_rebounds_percentage' => $match['DefensiveReboundsPercentage'] ?? null,
                    'total_rebounds_percentage' => $match['TotalReboundsPercentage'] ?? null,
                    'assists_percentage' => $match['AssistsPercentage'] ?? null,
                    'steals_percentage' => $match['StealsPercentage'] ?? null,
                    'blocks_percentage' => $match['BlocksPercentage'] ?? null,
                    'turnovers_percentage' => $match['TurnOversPercentage'] ?? null,

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
        $value = trim($value);

        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($ascii !== false) {
            $value = $ascii;
        }

        $value = strtolower($value);
        $value = str_replace(['’', '`', '´'], "'", $value);
        $value = preg_replace('/\b(jr|sr|ii|iii|iv|v)\b\.?/i', '', $value);
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value);

        return trim(preg_replace('/\s+/', ' ', $value));
    }

    private function namesMatch(string $player, string $apiName): bool
    {
        $playerNorm = $this->normalize($player);
        $apiNorm = $this->normalize($apiName);

        if ($playerNorm === $apiNorm) {
            return true;
        }

        $playerParts = preg_split('/\s+/', $playerNorm);
        $apiParts = preg_split('/\s+/', $apiNorm);

        if (!$playerParts || !$apiParts) {
            return false;
        }

        $playerLast = end($playerParts);
        $apiLast = end($apiParts);

        if ($playerLast !== $apiLast) {
            return false;
        }

        $playerFirst = $playerParts[0] ?? '';
        $apiFirst = $apiParts[0] ?? '';

        $playerInitial = substr($playerFirst, 0, 1);
        $apiInitial = substr($apiFirst, 0, 1);

        if ($playerInitial !== '' && $playerInitial === $apiInitial) {
            return true;
        }

        $playerNoSpaces = str_replace(' ', '', $playerNorm);
        $apiNoSpaces = str_replace(' ', '', $apiNorm);

        if ($playerNoSpaces === $apiNoSpaces) {
            return true;
        }

        return false;
    }

    private function findPlayerMatch($rows, string $player, ?string $teamKey)
    {
            $exact = $rows->first(function ($row) use ($player, $teamKey) {
                $rowName = $this->normalize($row['Name'] ?? '');
                $playerNorm = $this->normalize($player);
                $rowTeam = strtoupper(trim((string) ($row['Team'] ?? '')));
                $teamMatch = $teamKey ? $rowTeam === strtoupper($teamKey) : true;

                return $teamMatch && $rowName === $playerNorm;
            });

        if ($exact) {
            return $exact;
        }

        return $rows->first(function ($row) use ($player, $teamKey) {
            $rowTeam = strtoupper(trim((string) ($row['Team'] ?? '')));
            $teamMatch = $teamKey ? $rowTeam === strtoupper($teamKey) : true;

            return $teamMatch && $this->namesMatch($player, $row['Name'] ?? '');
        });
    }
}