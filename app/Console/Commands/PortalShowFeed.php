<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PortalShowFeed extends Command
{
    protected $signature = 'portal:feed 
                            {--limit=25}
                            {--confidence=}
                            {--team=}
                        ';
    protected $description = 'Show transfer portal feed preview';

    public function handle(): int
    {
        $rows = DB::table('portal_events as e')
            ->select(
                'e.player_name',
                'e.from_team',
                'e.first_reported_at',
                'e.report_count',
                'e.confidence',
                DB::raw('GROUP_CONCAT(DISTINCT s.handle) as reported_by')
            )
            ->leftJoin('portal_raw_posts as rp', 'rp.event_id', '=', 'e.id')
            ->leftJoin('portal_sources as s', 's.id', '=', 'rp.source_id')
            ->groupBy('e.id')
            ->orderByDesc('e.first_reported_at')
            ->limit((int)$this->option('limit'))
            ->when($this->option('confidence'), function ($q) {
                $q->where('e.confidence', $this->option('confidence'));
            })
            ->when($this->option('team'), function ($q) {
                $q->where('e.from_team', $this->option('team'));
            })
            ->get();

        foreach ($rows as $r) {
            $this->line(
                "{$r->player_name} | {$r->from_team} | {$r->report_count} reports | {$r->confidence} | {$r->reported_by}"
            );
        }

        return self::SUCCESS;
    }
}