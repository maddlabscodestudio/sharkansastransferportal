<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SportsDataIO\SportsDataIoClient;

class SportsDataIoTest extends Command
{
    protected $signature = 'sportsdataio:test {season=2026}';
    protected $description = 'Test SportsDataIO player season stats feed';

    public function handle(SportsDataIoClient $client): int
    {
        $season = $this->argument('season');

        $data = $client->get("/v3/cbb/stats/json/PlayerSeasonStats/{$season}");

        $this->info('Rows returned: ' . count($data));

        if (!empty($data[0])) {
            $this->line(json_encode($data[0], JSON_PRETTY_PRINT));
        }

        return self::SUCCESS;
    }
}