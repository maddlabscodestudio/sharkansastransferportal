<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class PortalStatsController extends Controller
{
    public function index(Request $request)
    {
        $season = (int) $request->get('season', 2026);
        // $limit = min((int) $request->get('limit', 100), 500);
        $limit = 500;

        $sort = $request->get('sort', 'first_reported_at');
        $dir = $request->get('dir', 'desc') === 'asc' ? 'asc' : 'desc';

        $search = trim((string) $request->get('search', ''));
        $position = trim((string) $request->get('position', ''));
        $minMpg = $request->get('min_mpg');
        $minPpg = $request->get('min_ppg');
        $minFg = $request->get('min_fg');
        $min3p = $request->get('min_3p');
        $minRpg = $request->get('min_rpg');
        $minApg = $request->get('min_apg');
        $minSpg = $request->get('min_spg');
        $minBpg = $request->get('min_bpg');
        $maxTopg = $request->get('max_topg');
        
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
                $join->on('e.sportsdataio_player_id', '=', 's.sportsdataio_player_id')
                    ->where('s.season', '=', $season);
        })
        ->leftJoin('players as p2', 'e.sportsdataio_player_id', '=', 'p2.sportsdataio_player_id');

        $query->whereNotNull('s.id');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('e.player_name', 'like', '%' . $search . '%')
                ->orWhere('e.from_team', 'like', '%' . $search . '%');
            });
        }

        if ($position !== '') {
            $position = strtoupper(trim($position));

            $query->whereRaw('UPPER(s.position) LIKE ?', ['%' . $position . '%']);
        }

        $players = $query
        ->select(
            'e.sportsdataio_player_id',
            'e.player_name',
            'p2.height',
            'p2.weight',
            'e.from_team',
            's.field_goals_made',
            's.field_goals_attempted',
            's.two_pointers_made',
            's.two_pointers_attempted',
            's.two_pointers_percentage',
            's.three_pointers_made',
            's.three_pointers_attempted',
            's.free_throws_made',
            's.free_throws_attempted',
            's.offensive_rebounds',
            's.defensive_rebounds',
            's.personal_fouls',
            's.effective_field_goals_percentage',
            's.offensive_rebounds_percentage',
            's.defensive_rebounds_percentage',
            's.total_rebounds_percentage',
            's.assists_percentage',
            's.steals_percentage',
            's.blocks_percentage',
            's.turnovers_percentage',
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
            'e.sportsdataio_player_id',
            'e.player_name',
            'p2.height',
            'p2.weight',
            'e.from_team',
            's.field_goals_made',
            's.field_goals_attempted',
            's.two_pointers_made',
            's.two_pointers_attempted',
            's.two_pointers_percentage',
            's.three_pointers_made',
            's.three_pointers_attempted',
            's.free_throws_made',
            's.free_throws_attempted',
            's.offensive_rebounds',
            's.defensive_rebounds',
            's.personal_fouls',
            's.effective_field_goals_percentage',
            's.offensive_rebounds_percentage',
            's.defensive_rebounds_percentage',
            's.total_rebounds_percentage',
            's.assists_percentage',
            's.steals_percentage',
            's.blocks_percentage',
            's.turnovers_percentage',
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
        ->get();

        if ($minMpg !== null && $minMpg !== '') {
            $players = $players->filter(function ($player) use ($minMpg) {
                return $player->mpg !== null && (float) $player->mpg >= (float) $minMpg;
            });
        }

        if ($minPpg !== null && $minPpg !== '') {
            $players = $players->filter(function ($player) use ($minPpg) {
                return $player->ppg !== null && (float) $player->ppg >= (float) $minPpg;
            });
        }

        if ($minFg !== null && $minFg !== '') {
            $players = $players->filter(function ($player) use ($minFg) {
                return $player->field_goals_percentage !== null && (float) $player->field_goals_percentage >= (float) $minFg;
            });
        }

        if ($min3p !== null && $min3p !== '') {
            $players = $players->filter(function ($player) use ($min3p) {
                return $player->three_pointers_percentage !== null
                    && (float) $player->three_pointers_percentage >= (float) $min3p;
            });
        }

        if ($minRpg !== null && $minRpg !== '') {
            $players = $players->filter(function ($player) use ($minRpg) {
                return $player->rpg !== null && (float) $player->rpg >= (float) $minRpg;
            });
        }

        if ($minApg !== null && $minApg !== '') {
            $players = $players->filter(function ($player) use ($minApg) {
                return $player->apg !== null && (float) $player->apg >= (float) $minApg;
            });
        }

        if ($minSpg !== null && $minSpg !== '') {
            $players = $players->filter(function ($player) use ($minSpg) {
                return $player->spg !== null && (float) $player->spg >= (float) $minSpg;
            });
        }

        if ($minBpg !== null && $minBpg !== '') {
            $players = $players->filter(function ($player) use ($minBpg) {
                return $player->bpg !== null && (float) $player->bpg >= (float) $minBpg;
            });
        }

        if ($maxTopg !== null && $maxTopg !== '') {
            $players = $players->filter(function ($player) use ($maxTopg) {
                return $player->tovpg !== null && (float) $player->tovpg <= (float) $maxTopg;
            });
        }

        $players = $dir === 'asc'
        ? $players->sortBy(fn($player) => (float) ($player->{$sort} ?? 0))
        : $players->sortByDesc(fn($player) => (float) ($player->{$sort} ?? 0));

        $players = $players->values()->take((int) $limit);

        // dd($players);
        return view('portal.stats', [
            'players' => $players,
            'season' => $season,
            'limit' => $limit,
            'search' => $search,
            'position' => $position,
            'minMpg' => $minMpg,
            'minPpg' => $minPpg,
            'minFg' => $minFg,
            'min3p' => $min3p,
            'minRpg' => $minRpg,
            'minApg' => $minApg,
            'minSpg' => $minSpg,
            'minBpg' => $minBpg,
            'maxTopg' => $maxTopg,
            'currentSort' => $sort,
            'currentDir' => $dir,
        ]);
    }
}