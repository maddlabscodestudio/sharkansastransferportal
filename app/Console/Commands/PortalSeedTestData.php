<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PortalSeedTestData extends Command
{
    protected $signature = 'portal:seed-test-data {--count=25 : Number of fake portal entries to create}';
    protected $description = 'Seed test transfer portal entries into the database';

    public function handle(): int
    {
        $count = (int) $this->option('count');

        // Minimal tables for now (super simple, one table).
        // We'll normalize later when we add sources/raw posts.
        DB::table('portal_entries')->insert(
            collect(range(1, $count))->map(function ($i) {
                $names = ['Jordan Smith', 'Jaylen Carter', 'Abdou Toure', 'Karter Knox', 'Billy Richmond'];
                $teams = ['Arkansas', 'Kansas', 'Duke', 'Auburn', 'Texas', 'Houston', 'Baylor'];

                $player = $names[array_rand($names)];
                $from = $teams[array_rand($teams)];

                // Random date within Mar–May 2025
                $timestamp = now()
                    ->setTimezone('UTC')
                    ->setDate(2025, rand(3, 5), rand(1, 28))
                    ->setTime(rand(0, 23), rand(0, 59), rand(0, 59));

                return [
                    'player_name' => $player,
                    'from_team'   => $from,
                    'status'      => 'entered',
                    'source'      => '@TestSource',
                    'posted_at'   => $timestamp,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ];
            })->all()
        );

        $this->info("Seeded {$count} test portal entries into portal_entries.");
        return self::SUCCESS;
    }
}