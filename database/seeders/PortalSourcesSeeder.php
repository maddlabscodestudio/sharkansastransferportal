<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PortalSourcesSeeder extends Seeder
{
    public function run(): void
    {
        $sources = [

            // Tier 1 — Core Aggregators
            ['handle' => 'VerbalCommits',   'tier' => 1, 'sport' => 'basketball', 'needs_sport_filter' => false],
            ['handle' => 'ThePortalReport', 'tier' => 1, 'sport' => 'basketball', 'needs_sport_filter' => false],
            ['handle' => 'LeagueRDY',       'tier' => 1, 'sport' => 'basketball', 'needs_sport_filter' => false],
            ['handle' => 'portal_updates',  'tier' => 1, 'sport' => 'basketball', 'needs_sport_filter' => false],
            
            // Tier 2 — National Reporters
            ['handle' => 'JeffBorzello',   'tier' => 2, 'sport' => 'basketball', 'needs_sport_filter' => false],
            ['handle' => 'TravisBranham_', 'tier' => 2, 'sport' => 'basketball', 'needs_sport_filter' => false],
            ['handle' => 'GoodmanHoops',   'tier' => 2, 'sport' => 'basketball', 'needs_sport_filter' => false],
            ['handle' => 'MikeSautter_',   'tier' => 2, 'sport' => 'basketball', 'needs_sport_filter' => false],

            // Tier 3 — Supplemental / Special Handling
            ['handle' => 'TrillyDonovan',  'tier' => 3, 'sport' => 'basketball', 'needs_sport_filter' => false],
            ['handle' => 'TransferPortal', 'tier' => 3, 'sport' => 'multi',      'needs_sport_filter' => true],
        ];

        foreach ($sources as $source) {
            DB::table('portal_sources')->updateOrInsert(
                ['handle' => $source['handle']],
                [
                    'tier' => $source['tier'],
                    'sport' => $source['sport'],
                    'needs_sport_filter' => $source['needs_sport_filter'],
                    'is_enabled' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}