<?php

namespace App\Console\Commands;

use App\Models\Player;
use App\Services\SportsDataIO\SportsDataIoClient;
use Illuminate\Console\Command;

class SyncPlayers extends Command
{
    protected $signature = 'sync:players {--refresh}';
    protected $description = 'Sync player bio data from SportsDataIO fantasy Players endpoint';

    public function handle(SportsDataIoClient $client): int
    {
        $basePath = storage_path('app/sportsdataio');

        if (!is_dir($basePath)) {
            mkdir($basePath, 0755, true);
        }

        $playersCachePath = "{$basePath}/players.json";

        if (!file_exists($playersCachePath) || $this->option('refresh')) {
            $this->info('Fetching fresh players from API...');

            $playersData = $client->get('/api/cbb/fantasy/json/Players');

            file_put_contents($playersCachePath, json_encode($playersData));

            $players = collect($playersData);
        } else {
            $this->info('Using cached players...');

            $players = collect(json_decode(file_get_contents($playersCachePath), true));
        }

        if ($players->isEmpty()) {
            $this->warn('No players returned.');
            return self::FAILURE;
        }

        $saved = 0;
        $skipped = 0;

        foreach ($players as $player) {
            $playerId = (int) ($player['PlayerID'] ?? 0);

            if (!$playerId) {
                $skipped++;
                continue;
            }

            $firstName = trim((string) ($player['FirstName'] ?? ''));
            $lastName = trim((string) ($player['LastName'] ?? ''));
            $fullName = trim((string) ($player['Name'] ?? trim($firstName . ' ' . $lastName)));

            Player::updateOrCreate(
                [
                    'sportsdataio_player_id' => $playerId,
                ],
                [
                    'first_name' => $firstName !== '' ? $firstName : null,
                    'last_name' => $lastName !== '' ? $lastName : null,
                    'full_name' => $fullName !== '' ? $fullName : null,
                    'team_key' => $player['Team'] ?? null,
                    'position' => $player['Position'] ?? null,
                    'height' => isset($player['Height']) && is_numeric($player['Height']) ? (int) $player['Height'] : null,
                    'weight' => isset($player['Weight']) && is_numeric($player['Weight']) ? (int) $player['Weight'] : null,
                    'raw_payload' => $player,
                ]
            );

            $saved++;
        }

        $this->newLine();
        $this->info("Done. Saved/updated {$saved} players, skipped {$skipped}.");

        return self::SUCCESS;
    }
}