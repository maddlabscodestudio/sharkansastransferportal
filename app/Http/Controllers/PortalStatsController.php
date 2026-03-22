<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class PortalStatsController extends Controller
{
    public function index(Request $request)
    {
        $season = (int) $request->get('season', 2026);
        $limit = min((int) $request->get('limit', 100), 500);
        $missingOnly = (string) $request->get('missing', '') === '1';

        $query = DB::table('portal_events as e')
            ->leftJoin('player_season_stats as s', function ($join) use ($season) {
                $join->on('e.player_name', '=', 's.player_name')
                    ->where('s.season', '=', $season);
            });

        if ($missingOnly) {
            $query->whereNull('s.id');
        }

        $players = $query
            ->select(
                'e.player_name',
                'e.from_team',
                DB::raw('MAX(e.first_reported_at) as first_reported_at'),
                's.team_name as stats_team_name',
                's.team_key as stats_team_key',
                's.games',
                's.points',
                's.rebounds',
                's.assists',
                's.true_shooting_percentage',
                's.position',
                's.synced_at'
            )
            ->groupBy(
                'e.player_name',
                'e.from_team',
                's.id',
                's.team_name',
                's.team_key',
                's.games',
                's.points',
                's.rebounds',
                's.assists',
                's.true_shooting_percentage',
                's.position',
                's.synced_at'
            )
            ->orderByDesc(DB::raw('MAX(e.first_reported_at)'))
            ->limit($limit)
            ->get();

        return view('portal.stats', [
            'players' => $players,
            'season' => $season,
            'limit' => $limit,
        ]);
    }
}