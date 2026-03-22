<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PortalFeedController extends Controller
{
    public function index(Request $request)
    {
        $limit = min((int) $request->query('limit', 50), 200);
        $team = $request->query('team');
        $confidence = $request->query('confidence');
        $flag = $request->query('flag'); // 'waiver' | 'no_eligibility'

        $events = DB::table('portal_events as e')
            ->select(
                'e.id',
                'e.player_name',
                'e.from_team',
                'e.first_reported_at',
                'e.report_count',
                'e.confidence',
                'e.needs_waiver',
                'e.no_eligibility_remaining',
                'e.eligibility_note',
                'stats.points',
                'stats.rebounds',
                'stats.assists',
                'stats.games',
                'stats.true_shooting_percentage',
                DB::raw("GROUP_CONCAT(DISTINCT '@' || s.handle) as reported_by")
            )
            ->leftJoin('player_season_stats as stats', function ($join) {
                $join->on('e.player_name', '=', 'stats.player_name')
                    ->where('stats.season', 2026);
            })
            ->leftJoin('portal_raw_posts as rp', 'rp.event_id', '=', 'e.id')
            ->leftJoin('portal_sources as s', 's.id', '=', 'rp.source_id')
            ->when($team, fn ($q) => $q->where('e.from_team', $team))
            ->when($confidence, fn ($q) => $q->where('e.confidence', $confidence))
            ->when($flag === 'waiver', fn ($q) => $q->where('e.needs_waiver', 1))
            ->when($flag === 'no_eligibility', fn ($q) => $q->where('e.no_eligibility_remaining', 1))
            ->groupBy('e.id')
            ->orderByDesc('e.first_reported_at')
            ->limit($limit)
            ->get();

        $teams = DB::table('portal_events')
            ->whereNotNull('from_team')
            ->select('from_team')
            ->distinct()
            ->orderBy('from_team')
            ->pluck('from_team');

        return view('portal.index', [
            'events' => $events,
            'teams' => $teams,
            'filters' => [
                'team' => $team,
                'confidence' => $confidence,
                'limit' => $limit,
                'flag' => $flag,
            ],
        ]);
    }
}