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
        $missingOnly = (string) $request->get('missing', '') === '1';

        $sort = $request->get('sort', 'first_reported_at');
        $dir = $request->get('dir', 'desc') === 'asc' ? 'asc' : 'desc';

        $sortMap = [
            'player_name' => 'e.player_name',
            'from_team' => 'e.from_team',
            'games' => 's.games',
            'mpg' => 'mpg',
            'ppg' => 'ppg',
            'rpg' => 'rpg',
            'apg' => 'apg',
            'spg' => 'spg',
            'bpg' => 'bpg',
            'tovpg' => 'tovpg',
            'field_goals_percentage' => 's.field_goals_percentage',
            'three_pointers_percentage' => 's.three_pointers_percentage',
            'free_throws_percentage' => 's.free_throws_percentage',
            'true_shooting_percentage' => 's.true_shooting_percentage',
            'player_efficiency_rating' => 's.player_efficiency_rating',
            'usage_rate_percentage' => 's.usage_rate_percentage',
            'position' => 's.position',
            'first_reported_at' => 'first_reported_at',
        ];

        if (!array_key_exists($sort, $sortMap)) {
            $sort = 'first_reported_at';
        }

        $orderColumn = $sortMap[$sort];

        $query = DB::table('portal_events as e')
            ->leftJoin('player_season_stats as s', function ($join) use ($season) {
                $join->on('e.player_name', '=', 's.player_name')
                    ->where('s.season', '=', $season);
            });

        if ($missingOnly) {
            $query->where(function ($q) {
                $q->whereNull('s.id')
                ->orWhereNull('s.field_goals_made')
                ->orWhereNull('s.field_goals_attempted')
                ->orWhereNull('s.effective_field_goals_percentage');
            });
        } else {
            $query->whereNotNull('s.id');
        }

        $players = $query
        ->select(
            'e.id as event_id',
            'e.player_name',
            'e.from_team',
            's.id as id',
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
            DB::raw('MAX(e.first_reported_at) as first_reported_at'),
            DB::raw('CASE WHEN s.games > 0 THEN ROUND(1.0 * s.minutes / s.games, 1) END as mpg'),
            DB::raw('CASE WHEN s.games > 0 THEN ROUND(1.0 * s.points / s.games, 1) END as ppg'),
            DB::raw('CASE WHEN s.games > 0 THEN ROUND(1.0 * s.rebounds / s.games, 1) END as rpg'),
            DB::raw('CASE WHEN s.games > 0 THEN ROUND(1.0 * s.assists / s.games, 1) END as apg'),
            DB::raw('CASE WHEN s.games > 0 THEN ROUND(1.0 * s.steals / s.games, 1) END as spg'),
            DB::raw('CASE WHEN s.games > 0 THEN ROUND(1.0 * s.blocked_shots / s.games, 1) END as bpg'),
            DB::raw('CASE WHEN s.games > 0 THEN ROUND(1.0 * s.turnovers / s.games, 1) END as tovpg')
        )
        ->groupBy(
            'e.id',
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
        ->orderBy($orderColumn, $dir)
        ->limit($limit)
        ->get();

        return view('portal.stats-manage', [
            'players' => $players,
            'season' => $season,
            'limit' => $limit,
            'missingOnly' => $missingOnly,
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
    
    public function destroyEvent(int $id)
    {
        DB::table('portal_events')->where('id', $id)->delete();

        return redirect()
            ->back()
            ->with('success', 'Portal event deleted.');
    }
}
