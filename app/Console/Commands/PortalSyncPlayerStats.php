<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SportsDataIO\SportsDataIoClient;
use App\Models\PlayerSeasonStat;
use Carbon\Carbon;

class PortalSyncPlayerStats extends Command
{
    protected $signature = 'portal:sync-player-stats {player} {team?} {season=2026}';
    protected $description = 'Find and store current-season stats for one player';

    public function handle(SportsDataIoClient $client): int
    {
        $player = trim((string) $this->argument('player'));
        $team = $this->argument('team') ? trim((string) $this->argument('team')) : null;
        $season = (int) $this->argument('season');

        $teams = collect($client->get('/api/cbb/fantasy/json/Teams'));
        $rows = collect($client->get("/api/cbb/fantasy/json/PlayerSeasonStats/{$season}"));

        if ($rows->isEmpty()) {
            $this->warn("No player season stats returned for season {$season}.");
            return self::FAILURE;
        }

        $teamKey = null;

        if ($team) {
            $normalizedTeam = $this->normalize($team);

            $matchedTeam = $teams->first(function ($teamRow) use ($normalizedTeam) {
                $school = $this->normalize($teamRow['School'] ?? '');
                $name = $this->normalize($teamRow['Name'] ?? '');
                $key = $this->normalize($teamRow['Key'] ?? '');

                return $school === $normalizedTeam
                    || $name === $normalizedTeam
                    || $key === $normalizedTeam;
            });

            if ($matchedTeam) {
                $teamKey = strtoupper(trim((string) ($matchedTeam['Key'] ?? '')));
                $this->info("Mapped team '{$team}' to SportsDataIO key '{$teamKey}'.");
            } else {
                $this->warn("Could not map team '{$team}' to a SportsDataIO team key.");
            }
        }

        $normalizedPlayer = $this->normalize($player);

        $matches = $rows->filter(function ($row) use ($normalizedPlayer, $teamKey) {
            $rowName = $this->normalize($row['Name'] ?? '');
            $rowTeam = strtoupper(trim((string) ($row['Team'] ?? '')));

            $nameMatch = $rowName === $normalizedPlayer;
            $teamMatch = $teamKey ? $rowTeam === $teamKey : true;

            return $nameMatch && $teamMatch;
        })->values();

        if ($matches->isEmpty()) {
            $this->warn("No exact match found for {$player}" . ($team ? " ({$team})" : '') . " in season {$season}.");
            $this->newLine();

            $candidateRows = $rows->filter(function ($row) use ($normalizedPlayer, $teamKey) {
                $rowName = $this->normalize($row['Name'] ?? '');
                $rowTeam = strtoupper(trim((string) ($row['Team'] ?? '')));

                $nameLooksClose =
                    str_contains($rowName, $normalizedPlayer) ||
                    str_contains($normalizedPlayer, $rowName) ||
                    similar_text($rowName, $normalizedPlayer) > 0;

                $teamLooksClose = $teamKey ? $rowTeam === $teamKey : true;

                return $nameLooksClose && $teamLooksClose;
            })->take(15)->values();

            if ($candidateRows->isNotEmpty()) {
                $this->info('Close candidates:');

                foreach ($candidateRows as $candidate) {
                    $this->line(json_encode([
                        'PlayerID' => $candidate['PlayerID'] ?? null,
                        'Name' => $candidate['Name'] ?? null,
                        'Team' => $candidate['Team'] ?? null,
                        'Position' => $candidate['Position'] ?? null,
                        'Points' => $candidate['Points'] ?? null,
                        'Rebounds' => $candidate['Rebounds'] ?? null,
                        'Assists' => $candidate['Assists'] ?? null,
                    ], JSON_PRETTY_PRINT));
                }
            }

            return self::FAILURE;
        }

        if ($matches->count() > 1) {
            $this->warn("Multiple matches found. Showing candidates:");

            foreach ($matches->take(10) as $match) {
                $this->line(json_encode([
                    'PlayerID' => $match['PlayerID'] ?? null,
                    'Name' => $match['Name'] ?? null,
                    'Team' => $match['Team'] ?? null,
                    'Points' => $match['Points'] ?? null,
                    'Rebounds' => $match['Rebounds'] ?? null,
                    'Assists' => $match['Assists'] ?? null,
                ], JSON_PRETTY_PRINT));
            }

            return self::FAILURE;
        }

        $match = $matches->first();
        $teamRows = $rows->filter(fn ($row) => strtoupper(trim((string) ($row['Team'] ?? ''))) === strtoupper(trim((string) ($match['Team'] ?? ''))))->values();
        $derived = $this->buildDerivedMetrics($match, $teamRows);

        PlayerSeasonStat::updateOrCreate(
            [
                'sportsdataio_player_id' => $match['PlayerID'],
                'season' => $match['Season'],
                'season_type' => $match['SeasonType'] ?? null,
            ],
            [
                'player_name' => $match['Name'] ?? $player,
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
                'field_goals_percentage' => $match['FieldGoalsPercentage'] ?? null,
                'three_pointers_percentage' => $match['ThreePointersPercentage'] ?? null,
                'free_throws_percentage' => $match['FreeThrowsPercentage'] ?? null,

                'effective_field_goals_percentage' => $derived['effective_field_goals_percentage'],
                'true_shooting_percentage' => $derived['true_shooting_percentage'],
                'usage_rate_percentage' => $derived['usage_rate_percentage'],
                'player_efficiency_rating' => $derived['player_efficiency_rating'],

                // not available in new fantasy endpoint; leave null for now
                'offensive_rebounds_percentage' => null,
                'defensive_rebounds_percentage' => null,
                'total_rebounds_percentage' => null,
                'assists_percentage' => null,
                'steals_percentage' => null,
                'blocks_percentage' => null,
                'turnovers_percentage' => $derived['turnovers_percentage'],

                'source_updated_at' => null,
                'synced_at' => now(),
                'raw_payload' => [
                    'player' => $match,
                    'team_totals' => $derived['team_totals'],
                ],
            ]
        );

        $this->info('Match found and saved:');
        $this->line(json_encode([
            'PlayerID' => $match['PlayerID'] ?? null,
            'Name' => $match['Name'] ?? null,
            'Team' => $match['Team'] ?? null,
            'Points' => $match['Points'] ?? null,
            'Rebounds' => $match['Rebounds'] ?? null,
            'Assists' => $match['Assists'] ?? null,
            'TS%' => $derived['true_shooting_percentage'],
            'USG%' => $derived['usage_rate_percentage'],
            'EFF' => $derived['player_efficiency_rating'],
        ], JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }

    private function buildDerivedMetrics(array $player, $teamRows): array
    {
        $points = (float) ($player['Points'] ?? 0);
        $fgm = (float) ($player['FieldGoalsMade'] ?? 0);
        $fga = (float) ($player['FieldGoalsAttempted'] ?? 0);
        $threesMade = (float) ($player['ThreePointersMade'] ?? 0);
        $ftm = (float) ($player['FreeThrowsMade'] ?? 0);
        $fta = (float) ($player['FreeThrowsAttempted'] ?? 0);
        $orb = (float) ($player['OffensiveRebounds'] ?? 0);
        $drb = (float) ($player['DefensiveRebounds'] ?? 0);
        $reb = (float) ($player['Rebounds'] ?? ($orb + $drb));
        $ast = (float) ($player['Assists'] ?? 0);
        $stl = (float) ($player['Steals'] ?? 0);
        $blk = (float) ($player['BlockedShots'] ?? 0);
        $tov = (float) ($player['Turnovers'] ?? 0);
        $pf = (float) ($player['PersonalFouls'] ?? 0);
        $minutes = (float) ($player['Minutes'] ?? 0);

        $teamFga = (float) $teamRows->sum(fn ($row) => (float) ($row['FieldGoalsAttempted'] ?? 0));
        $teamFta = (float) $teamRows->sum(fn ($row) => (float) ($row['FreeThrowsAttempted'] ?? 0));
        $teamTov = (float) $teamRows->sum(fn ($row) => (float) ($row['Turnovers'] ?? 0));
        $teamMinutes = (float) $teamRows->sum(fn ($row) => (float) ($row['Minutes'] ?? 0));

        $tsDenominator = 2 * ($fga + (0.44 * $fta));
        $trueShootingPercentage = $tsDenominator > 0
            ? round(($points / $tsDenominator) * 100, 1)
            : null;

        $effectiveFieldGoalsPercentage = $fga > 0
            ? round((($fgm + (0.5 * $threesMade)) / $fga) * 100, 1)
            : null;

        $turnoversPercentage = ($fga + (0.44 * $fta) + $tov) > 0
            ? round(($tov / ($fga + (0.44 * $fta) + $tov)) * 100, 1)
            : null;

        $usageDenominator = $minutes * ($teamFga + (0.44 * $teamFta) + $teamTov);
        $usageNumerator = ($fga + (0.44 * $fta) + $tov) * ($teamMinutes / 5);

        $usageRatePercentage = ($minutes > 0 && $usageDenominator > 0)
            ? round(($usageNumerator / $usageDenominator) * 100, 1)
            : null;

        $playerEfficiencyRating = round(
            $points
            + $reb
            + $ast
            + $stl
            + $blk
            - ($fga - $fgm)
            - ($fta - $ftm)
            - $tov
            - $pf,
            1
        );

        return [
            'effective_field_goals_percentage' => $effectiveFieldGoalsPercentage,
            'true_shooting_percentage' => $trueShootingPercentage,
            'usage_rate_percentage' => $usageRatePercentage,
            'player_efficiency_rating' => $playerEfficiencyRating,
            'turnovers_percentage' => $turnoversPercentage,
            'team_totals' => [
                'field_goals_attempted' => $teamFga,
                'free_throws_attempted' => $teamFta,
                'turnovers' => $teamTov,
                'minutes' => $teamMinutes,
            ],
        ];
    }

    private function normalize(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value);
        return trim($value);
    }
}