<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\Portal\PortalEventService;

class PortalBuildEvents extends Command
{
    protected $signature = 'portal:build-events';
    protected $description = 'Build canonical portal_events from portal_entries';

    public function handle(): int
    {
        $service = new PortalEventService();

        // Clear existing events
        DB::table('portal_events')->truncate();

        $entries = DB::table('portal_entries')->get();

        $count = 0;

        foreach ($entries as $entry) {
            $service->attachOrCreateEvent([
                'player_name' => $entry->player_name,
                'from_team' => $entry->from_team,
                'status' => $entry->status,
                'posted_at' => $entry->posted_at,
                'raw_post_id' => $entry->raw_post_id,
            ]);

            $count++;
        }

        $this->info("Processed {$count} entries into events.");

        return self::SUCCESS;
    }
}