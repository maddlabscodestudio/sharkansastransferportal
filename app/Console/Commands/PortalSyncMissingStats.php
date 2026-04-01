<?php

namespace App\Console\Commands;

use App\Models\PlayerSeasonStat;
use App\Services\SportsDataIO\SportsDataIoClient;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PortalSyncMissingStats extends Command
{
    protected $signature = 'portal:sync-missing-stats {season=2026} {--limit=500} {--refresh}';
    protected $description = 'Sync missing player season stats only for players in portal_events';

    public function handle(SportsDataIoClient $client): int
    {
        $season = (int) $this->argument('season');

        $this->info("Loading portal players for season {$season}...");

        $portalPlayers = DB::table('portal_events')
            ->select('player_name', 'from_team')
            ->distinct()
            ->get()
            ->filter(fn ($row) => !empty($row->player_name) && !empty($row->from_team))
            ->values();

        if ($portalPlayers->isEmpty()) {
            $this->warn("No portal players found in portal_events for season {$season}.");
            return self::FAILURE;
        }

        $this->info("Found {$portalPlayers->count()} distinct portal players.");

        $existingPlayerIds = PlayerSeasonStat::query()
            ->where('season', $season)
            ->whereNotNull('sportsdataio_player_id')
            ->pluck('sportsdataio_player_id')
            ->flip();

        $portalPlayersToSync = DB::table('portal_events as e')
            ->leftJoin('player_season_stats as s', function ($join) use ($season) {
                $join->on('e.sportsdataio_player_id', '=', 's.sportsdataio_player_id')
                    ->where('s.season', '=', $season);
            })
            ->select(
                'e.id',
                'e.player_name',
                'e.from_team',
                'e.sportsdataio_player_id'
            )
            ->where(function ($q) {
                $q->whereNull('e.sportsdataio_player_id')
                ->orWhereNull('s.id')
                ->orWhereNull('s.field_goals_made')
                ->orWhereNull('s.field_goals_attempted')
                ->orWhereNull('s.effective_field_goals_percentage');
            })
            ->distinct()
            ->get();

        if ($portalPlayersToSync->isEmpty()) {
            $this->info("No missing portal players to sync for season {$season}.");
            return self::SUCCESS;
        }

        $this->info("Checking {$portalPlayersToSync->count()} portal players.");

        $this->info("Fetching fantasy player season stats for {$season}...");
        $cachePath = storage_path("app/sportsdataio_player_season_stats_{$season}.json");

        if (!file_exists($cachePath) || $this->option('refresh')) {
            echo 'here';
            exit();
            $this->info("Fetching fresh stats from API...");

            $data = $client->get("/api/cbb/fantasy/json/PlayerSeasonStats/{$season}");

            file_put_contents($cachePath, json_encode($data));

            $seasonStats = collect($data);
        } else {
            $this->info("Using cached stats...");

            $seasonStats = collect(json_decode(file_get_contents($cachePath), true));
        }

        if ($seasonStats->isEmpty()) {
            $this->warn("No player season stats returned for season {$season}.");
            return self::FAILURE;
        }

        $teamsCachePath = storage_path("app/sportsdataio_teams.json");

        if (!file_exists($teamsCachePath) || $this->option('refresh')) {
            $this->info("Fetching fresh teams from API...");

            $teamsData = $client->get('/api/cbb/fantasy/json/Teams');

            file_put_contents($teamsCachePath, json_encode($teamsData));

            $teams = collect($teamsData);
        } else {
            $this->info("Using cached teams...");

            $teams = collect(json_decode(file_get_contents($teamsCachePath), true));
        }

        $saved = 0;
        $skipped = 0;

        foreach ($portalPlayersToSync as $portalPlayer) {
            $playerName = trim((string) $portalPlayer->player_name);
            $playerNameForMatch = $playerName;

            if ($this->normalize($playerName) === 'somto cyril') {
                $playerNameForMatch = 'Somtochukwu Cyril';
            }
            $teamName = trim((string) $portalPlayer->from_team);

            $teamKey = $this->mapTeamKey($teamName, $teams);

            if (!$teamKey) {
                $skipped++;
                $this->warn("Skipping {$playerName} - could not map portal team '{$teamName}'");
                continue;
            }

            $teamSeasonStats = $seasonStats->filter(function ($row) use ($teamKey) {
                return strtoupper(trim((string) ($row['Team'] ?? ''))) === $teamKey;
            })->values();

            

            $playerId = (int) ($match['PlayerID'] ?? 0);

            DB::table('portal_events')
            ->where('player_name', $playerName)
            ->where('from_team', $teamName)
            ->whereNull('sportsdataio_player_id')
            ->update([
                'sportsdataio_player_id' => $playerId,
                'updated_at' => now(),
            ]);

            if ($existingPlayerIds->has($playerId)) {
                // $this->line("Skipping existing: {$playerName}");
                continue;
            }

            DB::table('portal_events')
                ->where('player_name', $playerName)
                ->where('from_team', $teamName)
                ->whereNull('sportsdataio_player_id')
                ->update([
                    'sportsdataio_player_id' => $playerId,
                    'updated_at' => now(),
            ]);

            $match = $seasonStats->first(function ($row) use ($playerNameForMatch, $teamKey) {
                return $this->normalize($row['Name'] ?? '') === $this->normalize($playerNameForMatch)
                    && strtoupper(trim((string) ($row['Team'] ?? ''))) === $teamKey;
            });

            if (!$match) {
                $match = $seasonStats->first(function ($row) use ($playerNameForMatch, $teamKey) {
                    if (strtoupper(trim((string) ($row['Team'] ?? ''))) !== $teamKey) {
                        return false;
                    }

                    similar_text(
                        $this->normalize($row['Name'] ?? ''),
                        $this->normalize($playerNameForMatch),
                        $percent
                    );

                    return $percent >= 88;
                });
            }

            if (!$match) {
                $normalizedPlayer = $this->normalize($playerNameForMatch);
                $playerParts = explode(' ', $normalizedPlayer);

                $playerLast = end($playerParts);
                $playerFirst = $playerParts[0] ?? '';

                $match = $seasonStats->first(function ($row) use ($playerLast, $playerFirst) {
                    $name = $this->normalize($row['Name'] ?? '');
                    $parts = explode(' ', $name);

                    $rowLast = end($parts);
                    $rowFirst = $parts[0] ?? '';

                    return $rowLast === $playerLast
                        && (
                            str_contains($rowFirst, $playerFirst) ||
                            str_contains($playerFirst, $rowFirst)
                        );
                });
            }

            if (!$match) {
                $skipped++;
                $this->warn("No API match for {$playerName} ({$teamName} => {$teamKey})");
                continue;
            }

            $playerId = (int) ($match['PlayerID'] ?? 0);

            if (!$playerId) {
                $skipped++;
                $this->warn("Missing PlayerID for {$playerName}");
                continue;
            }

            DB::table('portal_events')
                ->where('player_name', $playerName)
                ->where('from_team', $teamName)
                ->whereNull('sportsdataio_player_id')
                ->update([
                    'sportsdataio_player_id' => $playerId,
                    'updated_at' => now(),
            ]);

            if ($existingPlayerIds->has($playerId)) {
                $this->line("Skipping existing: {$playerName}");
                continue;
            }

            PlayerSeasonStat::updateOrCreate(
                [
                    'sportsdataio_stat_id' => $match['StatID'],
                ],
                [
                    'sportsdataio_player_id' => $playerId,
                    'season' => (int) ($match['Season'] ?? $season),
                    'season_type' => $match['SeasonType'] ?? null,
                    'player_name' => $match['Name'],
                    'team_key' => $teamKey,
                    'team_name' => $teamName,
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
                    'synced_at' => now(),
                    'raw_payload' => $match,
                ]
            );

            $saved++;
            $this->line("Saved: {$playerName} ({$teamName})");
        }

        $this->newLine();
        $this->info("Done. Saved {$saved}, skipped {$skipped}.");

        return self::SUCCESS;
    }

    private function buildDerivedMetrics(array $player, Collection $teamRows): array
    {
        $points = (float) ($player['Points'] ?? 0);
        $fgm = (float) ($player['FieldGoalsMade'] ?? 0);
        $fga = (float) ($player['FieldGoalsAttempted'] ?? 0);
        $threesMade = (float) ($player['ThreePointersMade'] ?? 0);
        $ftm = (float) ($player['FreeThrowsMade'] ?? 0);
        $fta = (float) ($player['FreeThrowsAttempted'] ?? 0);
        $reb = (float) ($player['Rebounds'] ?? 0);
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
        $trueShootingPercentage = $tsDenominator > 0 ? round(($points / $tsDenominator) * 100, 1) : null;

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
            $points + $reb + $ast + $stl + $blk - ($fga - $fgm) - ($fta - $ftm) - $tov - $pf,
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

    private function mapTeamKey(string $teamName, Collection $teams): ?string
    {
        $aliases = [
            'san jose state' => 'SJSU',
            'san jose st' => 'SJSU',

            'etsu' => 'ETNST',
            'east tennessee state' => 'ETNST',

            'army west point' => 'ARMY',

            'cal state northridge' => 'CSUN',

            'the citadel d1' => 'CITA',
            'the citadel' => 'CITA',
        ];

        $normalizedTeam = $this->normalize($teamName);

        if (array_key_exists($normalizedTeam, $aliases)) {
            return $aliases[$normalizedTeam];
        }

        $matchedTeam = $teams->first(function ($teamRow) use ($normalizedTeam) {
            $school = $this->normalize($teamRow['School'] ?? '');
            $name = $this->normalize($teamRow['Name'] ?? '');
            $key = $this->normalize($teamRow['Key'] ?? '');

            return $school === $normalizedTeam
                || $name === $normalizedTeam
                || $key === $normalizedTeam;
        });

        return $matchedTeam ? strtoupper(trim((string) ($matchedTeam['Key'] ?? ''))) : null;
    }

    private function playerTeamKey(string $playerName, string $teamName): string
    {
        return $this->normalize($playerName) . '|' . $this->normalize($teamName);
    }

    private function normalize(string $value): string
    {
        $value = trim($value);
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $value = strtolower($value);
        $value = str_replace(['’', "'", '.', '-', '–', '—'], ' ', $value);
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value);

        return trim($value);
    }

    private function canonicalPlayerName(string $name): string
    {
        $aliases = [
            'somto cyril' => 'somtochukwu cyril',
        ];

        $normalized = $this->normalize($name);

        return $aliases[$normalized] ?? $name;
    }
}