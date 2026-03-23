<?php

namespace App\Http\Controllers;

use App\Models\PlayerSeasonStat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PortalStatsManageController extends Controller
{
    public function index(Request $request)
    {
        $season = (int) $request->get('season', 2026);
        $limit = min((int) $request->get('limit', 100), 500);
        // $missingOnly = (string) $request->get('missing', '') === '1';

        $sort = $request->get('sort', 'first_reported_at');
        $dir = $request->get('dir', 'desc') === 'asc' ? 'asc' : 'desc';

        $allowedSorts = [
            'player_name',
            'from_team',
            'games',
            'mpg',
            'ppg',
            'rpg',
            'apg',
            'spg',
            'bpg',
            'tovpg',
            'field_goals_percentage',
            'three_pointers_percentage',
            'free_throws_percentage',
            'true_shooting_percentage',
            'player_efficiency_rating',
            'usage_rate_percentage',
            'first_reported_at',
        ];

        if (!in_array($sort, $allowedSorts)) {
            $sort = 'first_reported_at';
        }

        $query = DB::table('portal_events as e')
            ->leftJoin('player_season_stats as s', function ($join) use ($season) {
                $join->on('e.player_name', '=', 's.player_name')
                    ->where('s.season', '=', $season);
            });

        $query->whereNotNull('s.id');

        // if ($missingOnly) {
        //     $query->whereNull('s.id');
        // }

        $players = $query
        ->select(
            'e.player_name',
            'e.from_team',
            DB::raw('MAX(e.first_reported_at) as first_reported_at'),

            's.games',
            's.minutes',
            's.points',
            's.rebounds',
            's.assists',
            's.steals',
            's.blocked_shots',
            's.turnovers',
            's.field_goals_percentage',
            's.three_pointers_percentage',
            's.free_throws_percentage',
            's.true_shooting_percentage',
            's.player_efficiency_rating',
            's.usage_rate_percentage',
            's.position',
            's.synced_at',

            DB::raw('CASE WHEN s.games > 0 THEN ROUND(1.0 * s.minutes / s.games, 1) END as mpg'),
            DB::raw('CASE WHEN s.games > 0 THEN ROUND(1.0 * s.points / s.games, 1) END as ppg'),
            DB::raw('CASE WHEN s.games > 0 THEN ROUND(1.0 * s.rebounds / s.games, 1) END as rpg'),
            DB::raw('CASE WHEN s.games > 0 THEN ROUND(1.0 * s.assists / s.games, 1) END as apg'),
            DB::raw('CASE WHEN s.games > 0 THEN ROUND(1.0 * s.steals / s.games, 1) END as spg'),
            DB::raw('CASE WHEN s.games > 0 THEN ROUND(1.0 * s.blocked_shots / s.games, 1) END as bpg'),
            DB::raw('CASE WHEN s.games > 0 THEN ROUND(1.0 * s.turnovers / s.games, 1) END as tovpg')
        )
        ->groupBy(
            'e.player_name',
            'e.from_team',
            's.id',
            's.games',
            's.minutes',
            's.points',
            's.rebounds',
            's.assists',
            's.steals',
            's.blocked_shots',
            's.turnovers',
            's.field_goals_percentage',
            's.three_pointers_percentage',
            's.free_throws_percentage',
            's.true_shooting_percentage',
            's.player_efficiency_rating',
            's.usage_rate_percentage',
            's.position',
            's.synced_at'
        )
        ->orderBy($sort, $dir)
        ->limit($limit)
        ->get();

        return view('portal.stats', [
            'players' => $players,
            'season' => $season,
            'limit' => $limit,
        ]);
    }

    public function destroy(int $id)
    {
        $row = PlayerSeasonStat::findOrFail($id);
        $row->delete();

        return redirect()
            ->back()
            ->with('success', 'Stat record deleted.');
    }
}
