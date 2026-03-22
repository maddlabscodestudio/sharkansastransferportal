<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PortalReset extends Command
{
    protected $signature = 'portal:reset';
    protected $description = 'Delete all portal entries';

    public function handle(): int
    {
        DB::table('portal_entries')->truncate();
        $this->info('portal_entries truncated.');
        return self::SUCCESS;
    }
}