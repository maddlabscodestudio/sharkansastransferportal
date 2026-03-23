<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sharkansas Portal Stats</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-900">
<div class="max-w-8xl mx-auto p-6">
    @php
        $currentSort = request('sort', 'first_reported_at');
        $currentDir = request('dir', 'desc');

        function sortDirFor($column, $currentSort, $currentDir) {
            return $currentSort === $column && $currentDir === 'asc' ? 'desc' : 'asc';
        }

        function sortArrow($column, $currentSort, $currentDir) {
            if ($currentSort !== $column) return '↕';
            return $currentDir === 'asc' ? '↑' : '↓';
        }

        function sortHeaderClass($column, $currentSort) {
            return $currentSort === $column
                ? 'text-sky-300 font-semibold'
                : 'text-slate-100 hover:text-sky-200';
        }

        function sortCellClass($column, $currentSort) {
            return $currentSort === $column ? 'bg-sky-50/70' : '';
        }

        function sortUrl($column, $currentSort, $currentDir) {
            return request()->fullUrlWithQuery([
                'sort' => $column,
                'dir' => sortDirFor($column, $currentSort, $currentDir),
            ]);
        }
    @endphp
    <div class="sticky top-0 z-20 bg-slate-100 pb-4">


        <div class="flex items-end justify-between gap-4 flex-wrap mb-6">
            <div>
                <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Portal Stats</h1>
                <p class="text-sm text-slate-600">Sortable transfer portal stats dashboard</p>
            </div>

            <form class="flex gap-2 flex-wrap" method="get" action="/portal-stats">
                <input
                    type="number"
                    name="season"
                    value="{{ $season }}"
                    class="border rounded px-3 py-2 text-sm w-28"
                />

                <input
                    type="number"
                    name="limit"
                    value="{{ $limit }}"
                    min="1"
                    max="500"
                    class="border rounded px-3 py-2 text-sm w-24"
                />

                <select name="missing" class="border rounded px-3 py-2 text-sm">
                    <option value="">All players</option>
                    <option value="1" @selected(request('missing') == 1)>Missing stats only</option>
                </select>

                <button class="bg-blue-700 hover:bg-blue-800 text-white rounded px-4 py-2 text-sm font-medium transition">
                    Filter
                </button>
            </form>
        </div>
    </div>

    <div class="mt-6 overflow-x-auto bg-white border border-slate-200 rounded-xl shadow-sm">
        <div class="max-h-[80vh] overflow-y-auto overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="sticky top-0 z-10 bg-slate-800 text-slate-100 text-left text-xs uppercase tracking-wider">
                <tr>
                    <th class="px-3 py-2">
                        <a href="{{ sortUrl('player_name', $currentSort, $currentDir) }}"
                        class="inline-flex items-center gap-1 transition-colors {{ sortHeaderClass('player_name', $currentSort) }}">
                            <span>Player</span>
                            <span class="text-[11px] leading-none">{{ sortArrow('player_name', $currentSort, $currentDir) }}</span>
                        </a>
                    </th>

                    <th class="px-3 py-2">
                        <a href="{{ sortUrl('from_team', $currentSort, $currentDir) }}"
                        class="inline-flex items-center gap-1 transition-colors {{ sortHeaderClass('from_team', $currentSort) }}">
                            <span>From</span>
                            <span class="text-[11px] leading-none">{{ sortArrow('from_team', $currentSort, $currentDir) }}</span>
                        </a>
                    </th>
                    <th class="px-3 py-2 text-center">Signals</th>

                    <th class="px-3 py-2 text-center">
                        <a href="{{ sortUrl('position', $currentSort, $currentDir) }}"
                        class="inline-flex items-center gap-1 transition-colors {{ sortHeaderClass('position', $currentSort) }}">
                            <span>Pos</span>
                            <span class="text-[11px] leading-none">{{ sortArrow('position', $currentSort, $currentDir) }}</span>
                        </a>
                    </th>

                    <th class="px-3 py-2 text-right">
                        <a href="{{ sortUrl('games', $currentSort, $currentDir) }}"
                        class="inline-flex items-center gap-1 transition-colors {{ sortHeaderClass('games', $currentSort) }}">
                            <span>G</span>
                            <span class="text-[11px] leading-none">{{ sortArrow('games', $currentSort, $currentDir) }}</span>
                        </a>
                    </th>

                    <th class="px-3 py-2 text-right">
                        <a href="{{ sortUrl('mpg', $currentSort, $currentDir) }}"
                        class="inline-flex items-center gap-1 transition-colors {{ sortHeaderClass('mpg', $currentSort) }}">
                            <span>MPG</span>
                            <span class="text-[11px] leading-none">{{ sortArrow('mpg', $currentSort, $currentDir) }}</span>
                        </a>
                    </th>

                    <th class="px-3 py-2 text-right">
                        <a href="{{ sortUrl('ppg', $currentSort, $currentDir) }}"
                        class="inline-flex items-center gap-1 transition-colors {{ sortHeaderClass('ppg', $currentSort) }}">
                            <span>PPG</span>
                            <span class="text-[11px] leading-none">{{ sortArrow('ppg', $currentSort, $currentDir) }}</span>
                        </a>
                    </th>

                    <th class="px-3 py-2 text-right">
                        <a href="{{ sortUrl('rpg', $currentSort, $currentDir) }}"
                        class="inline-flex items-center gap-1 transition-colors {{ sortHeaderClass('rpg', $currentSort) }}">
                            <span>RPG</span>
                            <span class="text-[11px] leading-none">{{ sortArrow('rpg', $currentSort, $currentDir) }}</span>
                        </a>
                    </th>

                    <th class="px-3 py-2 text-right">
                        <a href="{{ sortUrl('apg', $currentSort, $currentDir) }}"
                        class="inline-flex items-center gap-1 transition-colors {{ sortHeaderClass('apg', $currentSort) }}">
                            <span>APG</span>
                            <span class="text-[11px] leading-none">{{ sortArrow('apg', $currentSort, $currentDir) }}</span>
                        </a>
                    </th>

                    <th class="px-3 py-2 text-right">
                        <a href="{{ sortUrl('spg', $currentSort, $currentDir) }}"
                        class="inline-flex items-center gap-1 transition-colors {{ sortHeaderClass('spg', $currentSort) }}">
                            <span>SPG</span>
                            <span class="text-[11px] leading-none">{{ sortArrow('spg', $currentSort, $currentDir) }}</span>
                        </a>
                    </th>

                    <th class="px-3 py-2 text-right">
                        <a href="{{ sortUrl('bpg', $currentSort, $currentDir) }}"
                        class="inline-flex items-center gap-1 transition-colors {{ sortHeaderClass('bpg', $currentSort) }}">
                            <span>BPG</span>
                            <span class="text-[11px] leading-none">{{ sortArrow('bpg', $currentSort, $currentDir) }}</span>
                        </a>
                    </th>

                    <th class="px-3 py-2 text-right">
                        <a href="{{ sortUrl('tovpg', $currentSort, $currentDir) }}"
                        class="inline-flex items-center gap-1 transition-colors {{ sortHeaderClass('tovpg', $currentSort) }}">
                            <span>TOPG</span>
                            <span class="text-[11px] leading-none">{{ sortArrow('tovpg', $currentSort, $currentDir) }}</span>
                        </a>
                    </th>

                    <th class="px-3 py-2 text-right">
                        <a href="{{ sortUrl('field_goals_percentage', $currentSort, $currentDir) }}"
                        class="inline-flex items-center gap-1 transition-colors {{ sortHeaderClass('field_goals_percentage', $currentSort) }}">
                            <span>FG%</span>
                            <span class="text-[11px] leading-none">{{ sortArrow('field_goals_percentage', $currentSort, $currentDir) }}</span>
                        </a>
                    </th>

                    <th class="px-3 py-2 text-right">
                        <a href="{{ sortUrl('three_pointers_percentage', $currentSort, $currentDir) }}"
                        class="inline-flex items-center gap-1 transition-colors {{ sortHeaderClass('three_pointers_percentage', $currentSort) }}">
                            <span>3PT%</span>
                            <span class="text-[11px] leading-none">{{ sortArrow('three_pointers_percentage', $currentSort, $currentDir) }}</span>
                        </a>
                    </th>

                    <th class="px-3 py-2 text-right">
                        <a href="{{ sortUrl('free_throws_percentage', $currentSort, $currentDir) }}"
                        class="inline-flex items-center gap-1 transition-colors {{ sortHeaderClass('free_throws_percentage', $currentSort) }}">
                            <span>FT%</span>
                            <span class="text-[11px] leading-none">{{ sortArrow('free_throws_percentage', $currentSort, $currentDir) }}</span>
                        </a>
                    </th>

                    <th class="px-3 py-2 text-right">
                        <a href="{{ sortUrl('true_shooting_percentage', $currentSort, $currentDir) }}"
                        class="inline-flex items-center gap-1 transition-colors {{ sortHeaderClass('true_shooting_percentage', $currentSort) }}">
                            <span>TS%</span>
                            <span class="text-[11px] leading-none">{{ sortArrow('true_shooting_percentage', $currentSort, $currentDir) }}</span>
                        </a>
                    </th>

                    <th class="px-3 py-2 text-right">
                        <a href="{{ sortUrl('player_efficiency_rating', $currentSort, $currentDir) }}"
                        class="inline-flex items-center gap-1 transition-colors {{ sortHeaderClass('player_efficiency_rating', $currentSort) }}">
                            <span>PER</span>
                            <span class="text-[11px] leading-none">{{ sortArrow('player_efficiency_rating', $currentSort, $currentDir) }}</span>
                        </a>
                    </th>

                    <th class="px-3 py-2 text-right">
                        <a href="{{ sortUrl('usage_rate_percentage', $currentSort, $currentDir) }}"
                        class="inline-flex items-center gap-1 transition-colors {{ sortHeaderClass('usage_rate_percentage', $currentSort) }}">
                            <span>USG%</span>
                            <span class="text-[11px] leading-none">{{ sortArrow('usage_rate_percentage', $currentSort, $currentDir) }}</span>
                        </a>
                    </th>
                </tr>
                </thead>
            <tbody>
                @forelse($players as $p)
                    @php
                        $detailId = 'details-' . md5(($p->player_name ?? '') . '|' . ($p->from_team ?? '') . '|' . ($p->position ?? ''));
                    @endphp

                    <tr
                        class="cursor-pointer border-t border-slate-200 odd:bg-white even:bg-slate-50/80 hover:bg-sky-50 transition-colors duration-150"
                        onclick="
                            const row = document.getElementById('{{ $detailId }}');
                            const icon = document.getElementById('icon-{{ $detailId }}');
                            row.classList.toggle('hidden');
                            icon.textContent = row.classList.contains('hidden') ? '▸' : '▾';
                        "
                    >
                        <td class="px-3 py-2 font-semibold text-slate-900 whitespace-nowrap {{ sortCellClass('player_name', $currentSort) }}">
                            <div class="flex items-center gap-2">
                                <span id="icon-{{ $detailId }}" class="text-slate-400 text-xs">▸</span>
                                <span>{{ $p->player_name }}</span>
                            </div>
                        </td>

                        <td class="px-3 py-2 text-slate-700 whitespace-nowrap {{ sortCellClass('from_team', $currentSort) }}">
                            {{ $p->from_team }}
                        </td>

                        <td class="px-3 py-2 text-center whitespace-nowrap">
                            <div class="flex justify-center items-center gap-1 text-base leading-none">

                                @if($p->minutes !== null && $p->minutes < 100)
                                    <span class="inline-flex items-center justify-center w-5 h-5" title="Small sample size">⚠️</span>
                                @endif

                                @if($p->minutes !== null && $p->minutes >= 100)

                                    @if(
                                        $p->ppg !== null && $p->ppg >= 15 &&
                                        $p->true_shooting_percentage !== null && $p->true_shooting_percentage >= 58
                                    )
                                        <span class="inline-flex items-center justify-center w-5 h-5" title="High-volume efficient scorer">💥</span>
                                    @endif

                                    @if($p->ppg !== null && $p->ppg >= 18)
                                        <span class="inline-flex items-center justify-center w-5 h-5" title="Elite scorer">🔥</span>
                                    @endif

                                    @if($p->true_shooting_percentage !== null && $p->true_shooting_percentage >= 60)
                                        <span class="inline-flex items-center justify-center w-5 h-5" title="Highly efficient">🎯</span>
                                    @endif

                                    @if($p->apg !== null && $p->apg >= 4)
                                        <span class="inline-flex items-center justify-center w-5 h-5" title="Playmaker">🧠</span>
                                    @endif

                                    @if(
                                        ($p->spg !== null && $p->spg >= 1.5) ||
                                        ($p->bpg !== null && $p->bpg >= 1.5)
                                    )
                                        <span class="inline-flex items-center justify-center w-5 h-5" title="Defensive impact">🛡️</span>
                                    @endif

                                @endif

                            </div>
                        </td>

                        <td class="px-3 py-2 text-center text-slate-700 font-medium {{ sortCellClass('position', $currentSort) }}">
                            {{ $p->position ?? '—' }}
                        </td>

                        <td class="px-3 py-2 text-right text-slate-800 {{ sortCellClass('games', $currentSort) }}">
                            {{ $p->games ?? '—' }}
                        </td>

                        <td class="px-3 py-2 text-right text-slate-800 {{ sortCellClass('mpg', $currentSort) }}">
                            {{ $p->mpg ?? '—' }}
                        </td>

                        <td class="px-3 py-2 text-right font-semibold text-slate-900 {{ sortCellClass('ppg', $currentSort) }}">
                            {{ $p->ppg ?? '—' }}
                        </td>

                        <td class="px-3 py-2 text-right text-slate-800 {{ sortCellClass('rpg', $currentSort) }}">
                            {{ $p->rpg ?? '—' }}
                        </td>

                        <td class="px-3 py-2 text-right text-slate-800 {{ sortCellClass('apg', $currentSort) }}">
                            {{ $p->apg ?? '—' }}
                        </td>

                        <td class="px-3 py-2 text-right text-slate-800 {{ sortCellClass('spg', $currentSort) }}">
                            {{ $p->spg ?? '—' }}
                        </td>

                        <td class="px-3 py-2 text-right text-slate-800 {{ sortCellClass('bpg', $currentSort) }}">
                            {{ $p->bpg ?? '—' }}
                        </td>

                        <td class="px-3 py-2 text-right text-slate-800 {{ sortCellClass('tovpg', $currentSort) }}">
                            {{ $p->tovpg ?? '—' }}
                        </td>

                        <td class="px-3 py-2 text-right text-slate-800 {{ sortCellClass('field_goals_percentage', $currentSort) }}">
                            {{ $p->field_goals_percentage !== null ? round($p->field_goals_percentage, 1) : '—' }}
                        </td>

                        <td class="px-3 py-2 text-right text-slate-800 {{ sortCellClass('three_pointers_percentage', $currentSort) }}">
                            {{ $p->three_pointers_percentage !== null ? round($p->three_pointers_percentage, 1) : '—' }}
                        </td>

                        <td class="px-3 py-2 text-right text-slate-800 {{ sortCellClass('free_throws_percentage', $currentSort) }}">
                            {{ $p->free_throws_percentage !== null ? round($p->free_throws_percentage, 1) : '—' }}
                        </td>

                        <td class="px-3 py-2 text-right font-semibold text-sky-700 {{ sortCellClass('true_shooting_percentage', $currentSort) }}">
                            {{ $p->true_shooting_percentage !== null ? round($p->true_shooting_percentage, 1) : '—' }}
                        </td>

                        <td class="px-3 py-2 text-right text-slate-800 {{ sortCellClass('player_efficiency_rating', $currentSort) }}">
                            {{ $p->player_efficiency_rating !== null ? round($p->player_efficiency_rating, 1) : '—' }}
                        </td>

                        <td class="px-3 py-2 text-right {{ sortCellClass('usage_rate_percentage', $currentSort) }}">
                            @if($p->minutes !== null && $p->minutes < 100)
                                <span class="text-slate-400" title="Small sample size">
                                    {{ $p->usage_rate_percentage !== null ? round($p->usage_rate_percentage, 1) : '—' }}
                                </span>
                            @else
                                <span class="text-slate-800">
                                    {{ $p->usage_rate_percentage !== null ? round($p->usage_rate_percentage, 1) : '—' }}
                                </span>
                            @endif
                        </td>
                    </tr>

                    <tr id="{{ $detailId }}" class="hidden bg-slate-100 border-t border-slate-200">
                        <td colspan="18" class="px-5 py-4">
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 text-sm">
                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Overview</div>
                                    <div class="space-y-1 text-slate-700">
                                        <div><span class="font-medium text-slate-900">Player:</span> {{ $p->player_name }}</div>
                                        <div><span class="font-medium text-slate-900">From:</span> {{ $p->from_team }}</div>
                                        <div><span class="font-medium text-slate-900">Position:</span> {{ $p->position ?? '—' }}</div>
                                        <div><span class="font-medium text-slate-900">Games:</span> {{ $p->games ?? '—' }}</div>
                                        <div><span class="font-medium text-slate-900">Minutes:</span> {{ $p->minutes ?? '—' }}</div>
                                        <div><span class="font-medium text-slate-900">MPG:</span> {{ $p->mpg ?? '—' }}</div>
                                        <div><span class="font-medium text-slate-900">PF:</span> {{ $p->personal_fouls ?? '—' }}</div>
                                    </div>
                                </div>

                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Shooting Volume</div>
                                    <div class="space-y-1 text-slate-700">
                                        <div><span class="font-medium text-slate-900">FG:</span> {{ $p->field_goals_made ?? '—' }}/{{ $p->field_goals_attempted ?? '—' }}</div>
                                        <div><span class="font-medium text-slate-900">2PT:</span> {{ $p->two_pointers_made ?? '—' }}/{{ $p->two_pointers_attempted ?? '—' }}</div>
                                        <div><span class="font-medium text-slate-900">3PT:</span> {{ $p->three_pointers_made ?? '—' }}/{{ $p->three_pointers_attempted ?? '—' }}</div>
                                        <div><span class="font-medium text-slate-900">FT:</span> {{ $p->free_throws_made ?? '—' }}/{{ $p->free_throws_attempted ?? '—' }}</div>
                                        <div><span class="font-medium text-slate-900">FGA/G:</span> {{ ($p->games && $p->field_goals_attempted !== null) ? round($p->field_goals_attempted / $p->games, 1) : '—' }}</div>
                                        <div><span class="font-medium text-slate-900">3PA/G:</span> {{ ($p->games && $p->three_pointers_attempted !== null) ? round($p->three_pointers_attempted / $p->games, 1) : '—' }}</div>
                                        <div><span class="font-medium text-slate-900">FTA/G:</span> {{ ($p->games && $p->free_throws_attempted !== null) ? round($p->free_throws_attempted / $p->games, 1) : '—' }}</div>
                                    </div>
                                </div>

                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Percentages & Advanced</div>
                                    <div class="space-y-1 text-slate-700">
                                        <div><span class="font-medium text-slate-900">FG%:</span> {{ $p->field_goals_percentage !== null ? round($p->field_goals_percentage, 1) : '—' }}</div>
                                        <div><span class="font-medium text-slate-900">eFG%:</span> {{ $p->effective_field_goals_percentage !== null ? round($p->effective_field_goals_percentage, 1) : '—' }}</div>
                                        <div><span class="font-medium text-slate-900">2PT%:</span> {{ $p->two_pointers_percentage !== null ? round($p->two_pointers_percentage, 1) : '—' }}</div>
                                        <div><span class="font-medium text-slate-900">3PT%:</span> {{ $p->three_pointers_percentage !== null ? round($p->three_pointers_percentage, 1) : '—' }}</div>
                                        <div><span class="font-medium text-slate-900">FT%:</span> {{ $p->free_throws_percentage !== null ? round($p->free_throws_percentage, 1) : '—' }}</div>
                                        <div><span class="font-medium text-slate-900">TS%:</span> {{ $p->true_shooting_percentage !== null ? round($p->true_shooting_percentage, 1) : '—' }}</div>
                                        <div><span class="font-medium text-slate-900">PER:</span> {{ $p->player_efficiency_rating !== null ? round($p->player_efficiency_rating, 1) : '—' }}</div>
                                        <div>
                                            <span class="font-medium text-slate-900">USG%:</span>
                                            @if($p->minutes !== null && $p->minutes < 100)
                                                <span class="text-slate-400" title="Small sample size">
                                                    {{ $p->usage_rate_percentage !== null ? round($p->usage_rate_percentage, 1) : '—' }}
                                                </span>
                                            @else
                                                {{ $p->usage_rate_percentage !== null ? round($p->usage_rate_percentage, 1) : '—' }}
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Impact & Rates</div>
                                    <div class="space-y-1 text-slate-700">
                                        <div><span class="font-medium text-slate-900">ORB / DRB:</span> {{ $p->offensive_rebounds ?? '—' }} / {{ $p->defensive_rebounds ?? '—' }}</div>
                                        <div><span class="font-medium text-slate-900">TRB%:</span> {{ $p->total_rebounds_percentage !== null ? round($p->total_rebounds_percentage, 1) : '—' }}</div>
                                        <div><span class="font-medium text-slate-900">AST%:</span> {{ $p->assists_percentage !== null ? round($p->assists_percentage, 1) : '—' }}</div>
                                        <div><span class="font-medium text-slate-900">STL%:</span> {{ $p->steals_percentage !== null ? round($p->steals_percentage, 1) : '—' }}</div>
                                        <div><span class="font-medium text-slate-900">BLK%:</span> {{ $p->blocks_percentage !== null ? round($p->blocks_percentage, 1) : '—' }}</div>
                                        <div><span class="font-medium text-slate-900">TOV%:</span> {{ $p->turnovers_percentage !== null ? round($p->turnovers_percentage, 1) : '—' }}</div>
                                        <div><span class="font-medium text-slate-900">Stocks/G:</span> {{ ($p->games && $p->steals !== null && $p->blocked_shots !== null) ? round(($p->steals + $p->blocked_shots) / $p->games, 1) : '—' }}</div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="18" class="p-6 text-center text-gray-500">No portal players found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
</body>
</html>