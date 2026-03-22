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

        // Pull team directory so we can map "Florida" -> API team key
        $teams = collect($client->get('/v3/cbb/scores/json/teams'));

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
                $teamKey = $matchedTeam['Key'] ?? null;
                $this->info("Mapped team '{$team}' to SportsDataIO key '{$teamKey}'.");
            } else {
                $this->warn("Could not map team '{$team}' to a SportsDataIO team key.");
            }
        }

        $rows = $client->get("/v3/cbb/stats/json/PlayerSeasonStats/{$season}");

        $normalizedPlayer = $this->normalize($player);

        $matches = collect($rows)->filter(function ($row) use ($normalizedPlayer, $teamKey) {
            $rowName = $this->normalize($row['Name'] ?? '');
            $rowTeam = strtoupper(trim((string) ($row['Team'] ?? '')));

            $nameMatch = $rowName === $normalizedPlayer;
            $teamMatch = $teamKey ? $rowTeam === strtoupper($teamKey) : true;

            return $nameMatch && $teamMatch;
        })->values();

        if ($matches->isEmpty()) {
            $this->warn("No exact match found for {$player}" . ($team ? " ({$team})" : '') . " in season {$season}.");
            $this->newLine();

            $candidateRows = collect($rows)->filter(function ($row) use ($normalizedPlayer, $teamKey) {
                $rowName = $this->normalize($row['Name'] ?? '');
                $rowTeam = strtoupper(trim((string) ($row['Team'] ?? '')));

                $nameLooksClose =
                    str_contains($rowName, $normalizedPlayer) ||
                    str_contains($normalizedPlayer, $rowName) ||
                    similar_text($rowName, $normalizedPlayer) > 0;

                $teamLooksClose = $teamKey ? $rowTeam === strtoupper($teamKey) : true;

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

        $this->info('Match found and saved:');
        $this->line(json_encode([
            'PlayerID' => $match['PlayerID'] ?? null,
            'Name' => $match['Name'] ?? null,
            'Team' => $match['Team'] ?? null,
            'Points' => $match['Points'] ?? null,
            'Rebounds' => $match['Rebounds'] ?? null,
            'Assists' => $match['Assists'] ?? null,
        ], JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }

    private function normalize(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value);
        return trim($value);
    }
}