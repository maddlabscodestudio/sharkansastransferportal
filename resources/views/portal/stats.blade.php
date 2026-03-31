@extends('layouts.app')

@section('content')

<div class="max-w-8xl mx-auto p-6">
    @php
        $currentSort = request('sort', 'first_reported_at');
        $currentDir = request('dir', 'desc');

        $formatShortPlayerName = function ($fullName) {
            $fullName = trim((string) $fullName);

            if ($fullName === '') {
                return '—';
            }

            $parts = preg_split('/\s+/', $fullName);

            if (count($parts) === 1) {
                return $parts[0];
            }

            $firstInitial = mb_substr($parts[0], 0, 1);
            $lastName = $parts[count($parts) - 1];

            return $firstInitial . '. ' . $lastName;
        };

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
            return $currentSort === $column
            ? 'bg-blue-300/50 font-semibold text-slate-900'
            : '';
        }

        function sortUrl($column, $currentSort, $currentDir) {
            return request()->fullUrlWithQuery([
                'sort' => $column,
                'dir' => sortDirFor($column, $currentSort, $currentDir),
            ]);
        }
    @endphp
    <div class="sticky top-0 z-20 bg-white pb-2">
        <div class="text-center mb-6">
            <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight text-[#0B1F2D]">
                TRANSFER PORTAL STATS
            </h1>

            <p class="mt-3 text-slate-600 text-sm">
                Powered by 
                <a href="https://maddlabs.dev" target="_blank" class="text-[#00889c] hover:text-[#40cbd9] transition">
                    MaddLabs
                </a>
            </p>

            <div class="mt-4 w-24 h-[2px] bg-[#00889c] mx-auto"></div>
        </div>
    </div>

    <div class="mt-6 overflow-x-auto bg-transparent border border-slate-200 rounded-xl shadow-sm">
    <div class="max-h-[80vh] overflow-y-auto overflow-x-auto">
        <table class="w-full table-fixed md:table-auto min-w-[320px] md:min-w-[900px] text-sm">
            <thead class="sticky top-0 z-10 bg-slate-800 text-slate-100 text-center text-xs uppercase tracking-wider">
                <tr>
                    <th class="sticky left-0 z-20 bg-slate-800 px-2 py-2 w-[112px] min-w-[112px] max-w-[112px] md:w-auto md:min-w-[180px] md:max-w-none relative">
                        <div class="flex items-center gap-2">
                            <a href="{{ sortUrl('player_name', $currentSort, $currentDir) }}" class="inline-flex items-center gap-1 transition-colors {{ sortHeaderClass('player_name', $currentSort) }}">
                                <span>Player</span>
                                <span class="text-[11px] leading-none">{{ sortArrow('player_name', $currentSort, $currentDir) }}</span>
                            </a>

                            <button type="button" class="text-slate-300 hover:text-white text-xs" onclick="toggleFilterPopup('filter-player')">⌕</button>
                        </div>

                        <div id="filter-player" class="hidden absolute left-0 top-full mt-2 w-56 rounded border border-slate-700 bg-slate-900 p-3 shadow-lg">
                            <form method="get" action="/portal-stats" class="space-y-2">
                                <input type="hidden" name="limit" value="{{ $limit }}">
                                <input type="hidden" name="sort" value="{{ $currentSort }}">
                                <input type="hidden" name="dir" value="{{ $currentDir }}">
                                <input type="hidden" name="position" value="{{ $position ?? '' }}">
                                <input type="hidden" name="min_ppg" value="{{ $minPpg ?? '' }}">
                                <input type="hidden" name="min_3p" value="{{ $min3p ?? '' }}">

                                <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Player or team" class="w-full rounded border border-slate-600 bg-slate-950 px-2 py-1 text-sm text-slate-100">

                                <div class="flex gap-2">
                                    <button type="submit" class="rounded bg-blue-700 px-3 py-1 text-xs font-medium text-white hover:bg-blue-800">
                                        Apply
                                    </button>
                                    <a href="/portal-stats?limit={{ $limit }}&sort={{ $currentSort }}&dir={{ $currentDir }}" class="rounded bg-slate-700 px-3 py-1 text-xs text-white hover:bg-slate-600">
                                        Clear
                                    </a>
                                </div>
                            </form>
                        </div>
                    </th>

                    <!-- <th class="px-3 py-2 min-w-[120px]">
                        <a href="{{ sortUrl('from_team', $currentSort, $currentDir) }}"
                        class="inline-flex items-center gap-1 transition-colors {{ sortHeaderClass('from_team', $currentSort) }}">
                            <span>From</span>
                            <span class="text-[11px] leading-none">{{ sortArrow('from_team', $currentSort, $currentDir) }}</span>
                        </a>
                    </th> -->

                    <th class="px-2 py-2 text-center w-[64px] min-w-[64px] max-w-[64px] md:w-auto md:min-w-[90px] md:max-w-none">Signals</th>


                    <th class="px-2 py-2 text-right w-[56px] min-w-[56px] max-w-[56px] md:w-auto md:min-w-[72px] md:max-w-none relative">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ sortUrl('ppg', $currentSort, $currentDir) }}"
                            class="inline-flex items-center gap-1 transition-colors {{ sortHeaderClass('ppg', $currentSort) }}">
                                <span>PPG</span>
                                <span class="text-[11px] leading-none">{{ sortArrow('ppg', $currentSort, $currentDir) }}</span>
                            </a>

                            <button type="button" class="text-slate-300 hover:text-white text-xs" onclick="toggleFilterPopup('filter-ppg')" >⌕</button>
                        </div>

                        <div id="filter-ppg" class="hidden absolute right-0 top-full mt-2 w-40 rounded border border-slate-700 bg-slate-900 p-3 shadow-lg">
                            <form method="get" action="/portal-stats" class="space-y-2">
                                <input type="hidden" name="limit" value="{{ $limit }}">
                                <input type="hidden" name="sort" value="{{ $currentSort }}">
                                <input type="hidden" name="dir" value="{{ $currentDir }}">
                                <input type="hidden" name="search" value="{{ $search ?? '' }}">
                                <input type="hidden" name="position" value="{{ $position ?? '' }}">
                                <input type="hidden" name="min_3p" value="{{ $min3p ?? '' }}">

                                <input type="number" step="0.1" name="min_ppg" value="{{ $minPpg ?? '' }}" placeholder="Min PPG" class="w-full rounded border border-slate-600 bg-slate-950 px-2 py-1 text-sm text-slate-100">

                                <div class="flex gap-2">
                                    <button type="submit" class="rounded bg-blue-700 px-3 py-1 text-xs font-medium text-white hover:bg-blue-800">
                                        Apply
                                    </button>
                                    <a href="{{ request()->fullUrlWithQuery(['min_ppg' => null]) }}" class="rounded bg-slate-700 px-3 py-1 text-xs text-white hover:bg-slate-600">
                                        Clear
                                    </a>
                                </div>
                            </form>
                        </div>
                    </th>

                    <th class="hidden md:table-cell px-3 py-2 text-center">
                        <a href="{{ sortUrl('position', $currentSort, $currentDir) }}"
                        class="inline-flex items-center gap-1 transition-colors {{ sortHeaderClass('position', $currentSort) }}">
                            <span>Pos</span>
                            <span class="text-[11px] leading-none">{{ sortArrow('position', $currentSort, $currentDir) }}</span>
                        </a>
                    </th>

                    <th class="hidden md:table-cell px-3 py-2 text-center">
                        <a href="{{ sortUrl('games', $currentSort, $currentDir) }}"
                        class="inline-flex items-center gap-1 transition-colors {{ sortHeaderClass('games', $currentSort) }}">
                            <span>G</span>
                            <span class="text-[11px] leading-none">{{ sortArrow('games', $currentSort, $currentDir) }}</span>
                        </a>
                    </th>

                    <th class="hidden md:table-cell px-3 py-2 text-center relative">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ sortUrl('mpg', $currentSort, $currentDir) }}"
                            class="inline-flex items-center gap-1 transition-colors {{ sortHeaderClass('mpg', $currentSort) }}">
                                <span>MPG</span>
                                <span class="text-[11px] leading-none">{{ sortArrow('mpg', $currentSort, $currentDir) }}</span>
                            </a>

                            <button type="button" class="text-slate-300 hover:text-white text-xs" onclick="toggleFilterPopup('filter-mpg')">⌕</button>
                        </div>

                        <div id="filter-mpg" class="hidden absolute right-0 top-full mt-2 w-40 rounded border border-slate-700 bg-slate-900 p-3 shadow-lg z-30">
                            <form method="get" action="/portal-stats" class="space-y-2">
                                <input type="hidden" name="limit" value="{{ $limit }}">
                                <input type="hidden" name="sort" value="{{ $currentSort }}">
                                <input type="hidden" name="dir" value="{{ $currentDir }}">
                                <input type="hidden" name="search" value="{{ $search ?? '' }}">
                                <input type="hidden" name="position" value="{{ $position ?? '' }}">
                                <input type="hidden" name="min_ppg" value="{{ $minPpg ?? '' }}">
                                <input type="hidden" name="min_3p" value="{{ $min3p ?? '' }}">

                                <input type="number" step="0.1" name="min_mpg" value="{{ $minMpg ?? '' }}" placeholder="Min MPG" class="w-full rounded border border-slate-600 bg-slate-950 px-2 py-1 text-sm text-slate-100">

                                <div class="flex gap-2">
                                    <button type="submit" class="rounded bg-blue-700 px-3 py-1 text-xs font-medium text-white hover:bg-blue-800">
                                        Apply
                                    </button>
                                    <a href="{{ request()->fullUrlWithQuery(['min_mpg' => null]) }}" class="rounded bg-slate-700 px-3 py-1 text-xs text-white hover:bg-slate-600">
                                        Clear
                                    </a>
                                </div>
                            </form>
                        </div>
                    </th>

                    <th class="hidden md:table-cell px-3 py-2 text-center relative">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ sortUrl('rpg', $currentSort, $currentDir) }}"
                            class="inline-flex items-center gap-1 transition-colors {{ sortHeaderClass('rpg', $currentSort) }}">
                                <span>RPG</span>
                                <span class="text-[11px] leading-none">{{ sortArrow('rpg', $currentSort, $currentDir) }}</span>
                            </a>

                            <button type="button" class="text-slate-300 hover:text-white text-xs" onclick="toggleFilterPopup('filter-rpg')">⌕</button>
                        </div>

                        <div id="filter-rpg" class="hidden absolute right-0 top-full mt-2 w-40 rounded border border-slate-700 bg-slate-900 p-3 shadow-lg z-30">
                            <form method="get" action="/portal-stats" class="space-y-2">
                                <input type="hidden" name="limit" value="{{ $limit }}">
                                <input type="hidden" name="sort" value="{{ $currentSort }}">
                                <input type="hidden" name="dir" value="{{ $currentDir }}">
                                <input type="hidden" name="search" value="{{ $search ?? '' }}">
                                <input type="hidden" name="position" value="{{ $position ?? '' }}">
                                <input type="hidden" name="min_ppg" value="{{ $minPpg ?? '' }}">
                                <input type="hidden" name="min_mpg" value="{{ $minMpg ?? '' }}">
                                <input type="hidden" name="min_3p" value="{{ $min3p ?? '' }}">

                                <input type="number" step="0.1" name="min_rpg" value="{{ $minRpg ?? '' }}" placeholder="Min RPG" class="w-full rounded border border-slate-600 bg-slate-950 px-2 py-1 text-sm text-slate-100">

                                <div class="flex gap-2">
                                    <button type="submit" class="rounded bg-blue-700 px-3 py-1 text-xs font-medium text-white hover:bg-blue-800">
                                        Apply
                                    </button>
                                    <a href="{{ request()->fullUrlWithQuery(['min_rpg' => null]) }}" class="rounded bg-slate-700 px-3 py-1 text-xs text-white hover:bg-slate-600">
                                        Clear
                                    </a>
                                </div>
                            </form>
                        </div>
                    </th>

                    <th class="hidden md:table-cell px-3 py-2 text-center relative">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ sortUrl('apg', $currentSort, $currentDir) }}"
                            class="inline-flex items-center gap-1 transition-colors {{ sortHeaderClass('apg', $currentSort) }}">
                                <span>APG</span>
                                <span class="text-[11px] leading-none">{{ sortArrow('apg', $currentSort, $currentDir) }}</span>
                            </a>

                            <button type="button" class="text-slate-300 hover:text-white text-xs" onclick="toggleFilterPopup('filter-apg')">⌕</button>
                        </div>

                        <div id="filter-apg" class="hidden absolute right-0 top-full mt-2 w-40 rounded border border-slate-700 bg-slate-900 p-3 shadow-lg z-30">
                            <form method="get" action="/portal-stats" class="space-y-2">
                                <input type="hidden" name="limit" value="{{ $limit }}">
                                <input type="hidden" name="sort" value="{{ $currentSort }}">
                                <input type="hidden" name="dir" value="{{ $currentDir }}">
                                <input type="hidden" name="search" value="{{ $search ?? '' }}">
                                <input type="hidden" name="position" value="{{ $position ?? '' }}">
                                <input type="hidden" name="min_ppg" value="{{ $minPpg ?? '' }}">
                                <input type="hidden" name="min_mpg" value="{{ $minMpg ?? '' }}">
                                <input type="hidden" name="min_rpg" value="{{ $minRpg ?? '' }}">
                                <input type="hidden" name="min_3p" value="{{ $min3p ?? '' }}">

                                <input type="number" step="0.1" name="min_apg" value="{{ $minApg ?? '' }}" placeholder="Min APG" class="w-full rounded border border-slate-600 bg-slate-950 px-2 py-1 text-sm text-slate-100">

                                <div class="flex gap-2">
                                    <button type="submit" class="rounded bg-blue-700 px-3 py-1 text-xs font-medium text-white hover:bg-blue-800">
                                        Apply
                                    </button>
                                    <a href="{{ request()->fullUrlWithQuery(['min_apg' => null]) }}" class="rounded bg-slate-700 px-3 py-1 text-xs text-white hover:bg-slate-600">
                                        Clear
                                    </a>
                                </div>
                            </form>
                        </div>
                    </th>

                    <th class="hidden md:table-cell px-3 py-2 text-center relative">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ sortUrl('spg', $currentSort, $currentDir) }}"
                            class="inline-flex items-center gap-1 transition-colors {{ sortHeaderClass('spg', $currentSort) }}">
                                <span>SPG</span>
                                <span class="text-[11px] leading-none">{{ sortArrow('spg', $currentSort, $currentDir) }}</span>
                            </a>

                            <button type="button" class="text-slate-300 hover:text-white text-xs" onclick="toggleFilterPopup('filter-spg')">⌕</button>
                        </div>

                        <div id="filter-spg" class="hidden absolute right-0 top-full mt-2 w-40 rounded border border-slate-700 bg-slate-900 p-3 shadow-lg z-30">
                            <form method="get" action="/portal-stats" class="space-y-2">
                                <input type="hidden" name="limit" value="{{ $limit }}">
                                <input type="hidden" name="sort" value="{{ $currentSort }}">
                                <input type="hidden" name="dir" value="{{ $currentDir }}">
                                <input type="hidden" name="search" value="{{ $search ?? '' }}">
                                <input type="hidden" name="position" value="{{ $position ?? '' }}">
                                <input type="hidden" name="min_ppg" value="{{ $minPpg ?? '' }}">
                                <input type="hidden" name="min_mpg" value="{{ $minMpg ?? '' }}">
                                <input type="hidden" name="min_rpg" value="{{ $minRpg ?? '' }}">
                                <input type="hidden" name="min_apg" value="{{ $minApg ?? '' }}">
                                <input type="hidden" name="min_3p" value="{{ $min3p ?? '' }}">

                                <input type="number" step="0.1" name="min_spg" value="{{ $minSpg ?? '' }}" placeholder="Min SPG" class="w-full rounded border border-slate-600 bg-slate-950 px-2 py-1 text-sm text-slate-100" >

                                <div class="flex gap-2">
                                    <button type="submit" class="rounded bg-blue-700 px-3 py-1 text-xs font-medium text-white hover:bg-blue-800">
                                        Apply
                                    </button>
                                    <a href="{{ request()->fullUrlWithQuery(['min_spg' => null]) }}" class="rounded bg-slate-700 px-3 py-1 text-xs text-white hover:bg-slate-600">
                                        Clear
                                    </a>
                                </div>
                            </form>
                        </div>
                    </th>

                    <th class="hidden md:table-cell px-3 py-2 text-center relative">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ sortUrl('bpg', $currentSort, $currentDir) }}"
                            class="inline-flex items-center gap-1 transition-colors {{ sortHeaderClass('bpg', $currentSort) }}">
                                <span>BPG</span>
                                <span class="text-[11px] leading-none">{{ sortArrow('bpg', $currentSort, $currentDir) }}</span>
                            </a>

                            <button type="button" class="text-slate-300 hover:text-white text-xs" onclick="toggleFilterPopup('filter-bpg')" >⌕</button>
                        </div>

                        <div id="filter-bpg" class="hidden absolute right-0 top-full mt-2 w-40 rounded border border-slate-700 bg-slate-900 p-3 shadow-lg z-30">
                            <form method="get" action="/portal-stats" class="space-y-2">
                                <input type="hidden" name="limit" value="{{ $limit }}">
                                <input type="hidden" name="sort" value="{{ $currentSort }}">
                                <input type="hidden" name="dir" value="{{ $currentDir }}">
                                <input type="hidden" name="search" value="{{ $search ?? '' }}">
                                <input type="hidden" name="position" value="{{ $position ?? '' }}">
                                <input type="hidden" name="min_ppg" value="{{ $minPpg ?? '' }}">
                                <input type="hidden" name="min_mpg" value="{{ $minMpg ?? '' }}">
                                <input type="hidden" name="min_rpg" value="{{ $minRpg ?? '' }}">
                                <input type="hidden" name="min_apg" value="{{ $minApg ?? '' }}">
                                <input type="hidden" name="min_spg" value="{{ $minSpg ?? '' }}">
                                <input type="hidden" name="min_3p" value="{{ $min3p ?? '' }}">

                                <input type="number" step="0.1" name="min_bpg" value="{{ $minBpg ?? '' }}" placeholder="Min BPG" class="w-full rounded border border-slate-600 bg-slate-950 px-2 py-1 text-sm text-slate-100">

                                <div class="flex gap-2">
                                    <button type="submit" class="rounded bg-blue-700 px-3 py-1 text-xs font-medium text-white hover:bg-blue-800">
                                        Apply
                                    </button>
                                    <a href="{{ request()->fullUrlWithQuery(['min_bpg' => null]) }}" class="rounded bg-slate-700 px-3 py-1 text-xs text-white hover:bg-slate-600">
                                        Clear
                                    </a>
                                </div>
                            </form>
                        </div>
                    </th>
                    
                    <th class="hidden md:table-cell px-3 py-2 text-center relative">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ sortUrl('tovpg', $currentSort, $currentDir) }}"
                            class="inline-flex items-center gap-1 transition-colors {{ sortHeaderClass('tovpg', $currentSort) }}">
                                <span>TOPG</span>
                                <span class="text-[11px] leading-none">{{ sortArrow('tovpg', $currentSort, $currentDir) }}</span>
                            </a>

                            <button type="button" class="text-slate-300 hover:text-white text-xs" onclick="toggleFilterPopup('filter-topg')" >⌕</button>
                        </div>

                        <div id="filter-topg" class="hidden absolute right-0 top-full mt-2 w-40 rounded border border-slate-700 bg-slate-900 p-3 shadow-lg z-30">
                            <form method="get" action="/portal-stats" class="space-y-2">
                                <input type="hidden" name="limit" value="{{ $limit }}">
                                <input type="hidden" name="sort" value="{{ $currentSort }}">
                                <input type="hidden" name="dir" value="{{ $currentDir }}">
                                <input type="hidden" name="search" value="{{ $search ?? '' }}">
                                <input type="hidden" name="position" value="{{ $position ?? '' }}">
                                <input type="hidden" name="min_ppg" value="{{ $minPpg ?? '' }}">
                                <input type="hidden" name="min_mpg" value="{{ $minMpg ?? '' }}">
                                <input type="hidden" name="min_rpg" value="{{ $minRpg ?? '' }}">
                                <input type="hidden" name="min_apg" value="{{ $minApg ?? '' }}">
                                <input type="hidden" name="min_spg" value="{{ $minSpg ?? '' }}">
                                <input type="hidden" name="min_bpg" value="{{ $minBpg ?? '' }}">
                                <input type="hidden" name="min_3p" value="{{ $min3p ?? '' }}">

                                <input type="number" step="0.1" name="max_topg" value="{{ $maxTopg ?? '' }}" placeholder="Max TOPG" class="w-full rounded border border-slate-600 bg-slate-950 px-2 py-1 text-sm text-slate-100" >

                                <div class="flex gap-2">
                                    <button type="submit" class="rounded bg-blue-700 px-3 py-1 text-xs font-medium text-white hover:bg-blue-800">
                                        Apply
                                    </button>
                                    <a href="{{ request()->fullUrlWithQuery(['max_topg' => null]) }}" class="rounded bg-slate-700 px-3 py-1 text-xs text-white hover:bg-slate-600">
                                        Clear
                                    </a>
                                </div>
                            </form>
                        </div>
                    </th>

                    <th class="hidden md:table-cell px-3 py-2 text-center relative">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ sortUrl('field_goals_percentage', $currentSort, $currentDir) }}"
                            class="inline-flex items-center gap-1 transition-colors {{ sortHeaderClass('field_goals_percentage', $currentSort) }}">
                                <span>FG%</span>
                                <span class="text-[11px] leading-none">{{ sortArrow('field_goals_percentage', $currentSort, $currentDir) }}</span>
                            </a>

                            <button type="button" class="text-slate-300 hover:text-white text-xs" onclick="toggleFilterPopup('filter-fg')" >⌕</button>
                        </div>

                        <div id="filter-fg" class="hidden absolute right-0 top-full mt-2 w-40 rounded border border-slate-700 bg-slate-900 p-3 shadow-lg z-30">
                            <form method="get" action="/portal-stats" class="space-y-2">
                                <input type="hidden" name="limit" value="{{ $limit }}">
                                <input type="hidden" name="sort" value="{{ $currentSort }}">
                                <input type="hidden" name="dir" value="{{ $currentDir }}">
                                <input type="hidden" name="search" value="{{ $search ?? '' }}">
                                <input type="hidden" name="position" value="{{ $position ?? '' }}">
                                <input type="hidden" name="min_ppg" value="{{ $minPpg ?? '' }}">
                                <input type="hidden" name="min_mpg" value="{{ $minMpg ?? '' }}">
                                <input type="hidden" name="min_rpg" value="{{ $minRpg ?? '' }}">
                                <input type="hidden" name="min_apg" value="{{ $minApg ?? '' }}">
                                <input type="hidden" name="min_spg" value="{{ $minSpg ?? '' }}">
                                <input type="hidden" name="min_bpg" value="{{ $minBpg ?? '' }}">
                                <input type="hidden" name="min_3p" value="{{ $min3p ?? '' }}">
                                <input type="hidden" name="max_topg" value="{{ $maxTopg ?? '' }}">

                                <input type="number" step="0.1" name="min_fg" value="{{ $minFg ?? '' }}" placeholder="Min FG%" class="w-full rounded border border-slate-600 bg-slate-950 px-2 py-1 text-sm text-slate-100">

                                <div class="flex gap-2"><button type="submit" class="rounded bg-blue-700 px-3 py-1 text-xs font-medium text-white hover:bg-blue-800">Apply</button><a href="{{ request()->fullUrlWithQuery(['min_fg' => null]) }}" class="rounded bg-slate-700 px-3 py-1 text-xs text-white hover:bg-slate-600">Clear</a></div>
                            </form>
                        </div>
                    </th>

                    <th class="hidden md:table-cell px-3 py-2 text-center relative">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ sortUrl('three_pointers_percentage', $currentSort, $currentDir) }}" class="inline-flex items-center gap-1 transition-colors {{ sortHeaderClass('three_pointers_percentage', $currentSort) }}">
                                <span>3PT%</span>
                                <span class="text-[11px] leading-none">{{ sortArrow('three_pointers_percentage', $currentSort, $currentDir) }}</span>
                            </a>
                            <button type="button" class="text-slate-300 hover:text-white text-xs" onclick="toggleFilterPopup('filter-3pt')">⌕</button>
                        </div>

                        <div id="filter-3pt" class="hidden absolute right-0 top-full mt-2 w-40 rounded border border-slate-700 bg-slate-900 p-3 shadow-lg z-30">
                            <form method="get" action="/portal-stats" class="space-y-2">
                                <input type="hidden" name="limit" value="{{ $limit }}">
                                <input type="hidden" name="sort" value="{{ $currentSort }}">
                                <input type="hidden" name="dir" value="{{ $currentDir }}">
                                <input type="hidden" name="search" value="{{ $search ?? '' }}">
                                <input type="hidden" name="position" value="{{ $position ?? '' }}">
                                <input type="hidden" name="min_ppg" value="{{ $minPpg ?? '' }}">
                                <input type="hidden" name="min_mpg" value="{{ $minMpg ?? '' }}">
                                <input type="hidden" name="min_rpg" value="{{ $minRpg ?? '' }}">
                                <input type="hidden" name="min_apg" value="{{ $minApg ?? '' }}">
                                <input type="hidden" name="min_spg" value="{{ $minSpg ?? '' }}">
                                <input type="hidden" name="min_bpg" value="{{ $minBpg ?? '' }}">
                                <input type="hidden" name="min_fg" value="{{ $minFg ?? '' }}">
                                <input type="hidden" name="max_topg" value="{{ $maxTopg ?? '' }}">

                                <input type="number" step="0.1" name="min_3p" value="{{ $min3p ?? '' }}" placeholder="Min 3PT%" class="w-full rounded border border-slate-600 bg-slate-950 px-2 py-1 text-sm text-slate-100">

                                <div class="flex gap-2 items-center"><button type="submit" class="rounded bg-blue-700 px-3 py-1 text-xs font-medium text-white hover:bg-blue-800">Apply</button><a href="{{ request()->fullUrlWithQuery(['min_3p' => null]) }}" class="rounded bg-slate-700 px-3 py-1 text-xs text-white hover:bg-slate-600">Clear</a></div>
                            </form>
                        </div>
                    </th>

                    <th class="hidden md:table-cell px-3 py-2 text-center">
                        <a href="{{ sortUrl('free_throws_percentage', $currentSort, $currentDir) }}"
                        class="inline-flex items-center gap-1 transition-colors {{ sortHeaderClass('free_throws_percentage', $currentSort) }}">
                            <span>FT%</span>
                            <span class="text-[11px] leading-none">{{ sortArrow('free_throws_percentage', $currentSort, $currentDir) }}</span>
                        </a>
                    </th>

                    <th class="hidden md:table-cell px-3 py-2 text-center">
                        <a href="{{ sortUrl('true_shooting_percentage', $currentSort, $currentDir) }}"
                        class="inline-flex items-center gap-1 transition-colors {{ sortHeaderClass('true_shooting_percentage', $currentSort) }}">
                            <span>TS%</span>
                            <span class="text-[11px] leading-none">{{ sortArrow('true_shooting_percentage', $currentSort, $currentDir) }}</span>
                        </a>
                    </th>

                    <th class="hidden md:table-cell px-3 py-2 text-center">
                        <a href="{{ sortUrl('player_efficiency_rating', $currentSort, $currentDir) }}"
                        class="inline-flex items-center gap-1 transition-colors {{ sortHeaderClass('player_efficiency_rating', $currentSort) }}">
                            <span>PER</span>
                            <span class="text-[11px] leading-none">{{ sortArrow('player_efficiency_rating', $currentSort, $currentDir) }}</span>
                        </a>
                    </th>

                    <th class="hidden md:table-cell px-3 py-2 text-center">
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

                    <tr class="cursor-pointer border-t border-slate-200 odd:bg-transparent even:bg-slate-200 hover:bg-sky-300 transition-colors duration-150" onclick="togglePlayerDetails('{{ $detailId }}')">
                        <td class="sticky left-0 z-[5] bg-slate-900 px-2 py-2 font-semibold text-white whitespace-nowrap shadow-[2px_0_0_0_rgba(226,232,240,1)] w-[112px] min-w-[112px] max-w-[112px] md:w-auto md:min-w-[180px] md:max-w-none {{ sortCellClass('player_name', $currentSort) }}">
                            <div class="flex items-center gap-2 overflow-hidden">
                                <span id="icon-{{ $detailId }}" class="text-slate-400 text-xs shrink-0">▸</span>
                                <div class="min-w-0 overflow-hidden">
                                    <div class="truncate max-w-[72px] md:max-w-none">
                                        <span class="md:hidden">{{ $formatShortPlayerName($p->player_name) }}</span>
                                        <span class="hidden md:inline">{{ $p->player_name }}</span>
                                    </div>
                                    <div class="text-[11px] text-slate-400 md:hidden truncate">
                                        {{ $p->position ?? '—' }}
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- <td class="px-3 py-2 text-slate-700 whitespace-nowrap bg-transparent {{ sortCellClass('from_team', $currentSort) }}">
                            {{ $p->from_team }}
                        </td> -->

                        <td class="px-2 py-2 text-center whitespace-nowrap bg-transparent w-[64px] min-w-[64px] max-w-[64px] md:w-auto md:min-w-[90px] md:max-w-none">
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

                        <td class="px-2 py-2 text-center font-semibold text-slate-900 bg-transparent w-[64px] min-w-[64px] max-w-[64px] md:w-auto md:min-w-[72px] md:max-w-none {{ sortCellClass('ppg', $currentSort) }}">
                            {{ $p->ppg ?? '—' }}
                        </td>

                        <td class="hidden md:table-cell px-3 py-2 text-center text-slate-700 font-medium bg-transparent {{ sortCellClass('position', $currentSort) }}">
                            {{ $p->position ?? '—' }}
                        </td>

                        <td class="hidden md:table-cell px-3 py-2 text-center text-slate-800 bg-transparent {{ sortCellClass('games', $currentSort) }}">
                            {{ $p->games ?? '—' }}
                        </td>

                        <td class="hidden md:table-cell px-3 py-2 text-center text-slate-800 bg-transparent {{ sortCellClass('mpg', $currentSort) }}">
                            {{ $p->mpg ?? '—' }}
                        </td>

                        <td class="hidden md:table-cell px-3 py-2 text-center text-slate-800 bg-transparent {{ sortCellClass('rpg', $currentSort) }}">
                            {{ $p->rpg ?? '—' }}
                        </td>

                        <td class="hidden md:table-cell px-3 py-2 text-center text-slate-800 bg-transparent {{ sortCellClass('apg', $currentSort) }}">
                            {{ $p->apg ?? '—' }}
                        </td>

                        <td class="hidden md:table-cell px-3 py-2 text-center text-slate-800 bg-transparent {{ sortCellClass('spg', $currentSort) }}">
                            {{ $p->spg ?? '—' }}
                        </td>

                        <td class="hidden md:table-cell px-3 py-2 text-center text-slate-800 bg-transparent {{ sortCellClass('bpg', $currentSort) }}">
                            {{ $p->bpg ?? '—' }}
                        </td>

                        <td class="hidden md:table-cell px-3 py-2 text-center text-slate-800 bg-transparent {{ sortCellClass('tovpg', $currentSort) }}">
                            {{ $p->tovpg ?? '—' }}
                        </td>

                        <td class="hidden md:table-cell px-3 py-2 text-center text-slate-800 bg-transparent {{ sortCellClass('field_goals_percentage', $currentSort) }}">
                            {{ $p->field_goals_percentage !== null ? round($p->field_goals_percentage, 1) : '—' }}
                        </td>

                        <td class="hidden md:table-cell px-3 py-2 text-center text-slate-800 bg-transparent {{ sortCellClass('three_pointers_percentage', $currentSort) }}">
                            {{ $p->three_pointers_percentage !== null ? round($p->three_pointers_percentage, 1) : '—' }}
                        </td>

                        <td class="hidden md:table-cell px-3 py-2 text-center text-slate-800 bg-transparent {{ sortCellClass('free_throws_percentage', $currentSort) }}">
                            {{ $p->free_throws_percentage !== null ? round($p->free_throws_percentage, 1) : '—' }}
                        </td>

                        <td class="hidden md:table-cell px-3 py-2 text-center font-semibold text-sky-700 bg-transparent {{ sortCellClass('true_shooting_percentage', $currentSort) }}">
                            {{ $p->true_shooting_percentage !== null ? round($p->true_shooting_percentage, 1) : '—' }}
                        </td>

                        <td class="hidden md:table-cell px-3 py-2 text-center text-slate-800 bg-transparent {{ sortCellClass('player_efficiency_rating', $currentSort) }}">
                            {{ $p->player_efficiency_rating !== null ? round($p->player_efficiency_rating, 1) : '—' }}
                        </td>

                        <td class="hidden md:table-cell px-3 py-2 text-center bg-transparent {{ sortCellClass('usage_rate_percentage', $currentSort) }}">
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

                    {{-- Mobile expanded row --}}
                    <tr id="{{ $detailId }}-mobile" class="hidden md:hidden bg-slate-900/95">
                        <td colspan="3" class="px-3 py-3">
                            <div class="w-full rounded-2xl border border-slate-700 bg-slate-800/70 px-4 py-4 text-center shadow-lg">
                                <div class="grid gap-4 grid-cols-1">

                                    <div class="rounded-xl border border-slate-700 bg-slate-900/60 p-4">
                                        <h4 class="mb-3 text-xs font-semibold uppercase tracking-[0.18em] text-sky-300">Overview</h4>
                                        <div class="space-y-2 text-sm text-slate-200">
                                            <div><span class="text-slate-400">Player:</span> {{ $p->player_name }}</div>
                                            <div><span class="text-slate-400">From:</span> {{ $p->from_team }}</div>
                                            <div><span class="text-slate-400">Position:</span> {{ $p->position ?? '—' }}</div>
                                            <div><span class="text-slate-400">Games:</span> {{ $p->games ?? '—' }}</div>
                                            <div><span class="text-slate-400">Minutes:</span> {{ $p->minutes ?? '—' }}</div>
                                            <div><span class="text-slate-400">MPG:</span> {{ $p->mpg ?? '—' }}</div>
                                            <div><span class="text-slate-400">PF:</span> {{ $p->personal_fouls ?? '—' }}</div>
                                        </div>
                                    </div>

                                    <div class="rounded-xl border border-slate-700 bg-slate-900/60 p-4">
                                        <h4 class="mb-3 text-xs font-semibold uppercase tracking-[0.18em] text-sky-300">Shooting Volume</h4>
                                        <div class="grid grid-cols-2 gap-3 text-sm text-slate-200">
                                            <div><div class="text-slate-400">FTA/G</div><div>{{ ($p->games && $p->free_throws_attempted !== null) ? round($p->free_throws_attempted / $p->games, 1) : '—' }}</div></div>
                                            <div><div class="text-slate-400">2PT</div><div>{{ $p->two_pointers_made ?? '—' }}/{{ $p->two_pointers_attempted ?? '—' }}</div></div>
                                            <div><div class="text-slate-400">3PT</div><div>{{ $p->three_pointers_made ?? '—' }}/{{ $p->three_pointers_attempted ?? '—' }}</div></div>
                                            <div><div class="text-slate-400">FT</div><div>{{ $p->free_throws_made ?? '—' }}/{{ $p->free_throws_attempted ?? '—' }}</div></div>
                                            <div><div class="text-slate-400">FGA/G</div><div>{{ ($p->games && $p->field_goals_attempted !== null) ? round($p->field_goals_attempted / $p->games, 1) : '—' }}</div></div>
                                            <div><div class="text-slate-400">3PA/G</div><div>{{ ($p->games && $p->three_pointers_attempted !== null) ? round($p->three_pointers_attempted / $p->games, 1) : '—' }}</div></div>
                                            <div class="col-span-2"><div class="text-slate-400">FG</div><div>{{ $p->field_goals_made ?? '—' }}/{{ $p->field_goals_attempted ?? '—' }}</div></div>
                                        </div>
                                    </div>

                                    <div class="rounded-xl border border-slate-700 bg-slate-900/60 p-4">
                                        <h4 class="mb-3 text-xs font-semibold uppercase tracking-[0.18em] text-sky-300">Percentages & Advanced</h4>
                                        <div class="grid grid-cols-2 gap-3 text-sm text-slate-200">
                                            <div><div class="text-slate-400">FG%</div><div>{{ $p->field_goals_percentage !== null ? round($p->field_goals_percentage, 1) : '—' }}</div></div>
                                            <div><div class="text-slate-400">eFG%</div><div>{{ $p->effective_field_goals_percentage !== null ? round($p->effective_field_goals_percentage, 1) : '—' }}</div></div>
                                            <div><div class="text-slate-400">2PT%</div><div>{{ $p->two_pointers_percentage !== null ? round($p->two_pointers_percentage, 1) : '—' }}</div></div>
                                            <div><div class="text-slate-400">3PT%</div><div>{{ $p->three_pointers_percentage !== null ? round($p->three_pointers_percentage, 1) : '—' }}</div></div>
                                            <div><div class="text-slate-400">FT%</div><div>{{ $p->free_throws_percentage !== null ? round($p->free_throws_percentage, 1) : '—' }}</div></div>
                                            <div><div class="text-slate-400">TS%</div><div>{{ $p->true_shooting_percentage !== null ? round($p->true_shooting_percentage, 1) : '—' }}</div></div>
                                            <div><div class="text-slate-400">PER</div><div>{{ $p->player_efficiency_rating !== null ? round($p->player_efficiency_rating, 1) : '—' }}</div></div>
                                            <div><div class="text-slate-400">USG%</div><div>{{ $p->usage_rate_percentage !== null ? round($p->usage_rate_percentage, 1) : '—' }}</div></div>
                                        </div>
                                    </div>

                                    <div class="rounded-xl border border-slate-700 bg-slate-900/60 p-4">
                                        <h4 class="mb-3 text-xs font-semibold uppercase tracking-[0.18em] text-sky-300">Impact & Rates</h4>
                                        <div class="grid grid-cols-2 gap-3 text-sm text-slate-200">
                                            <div><div class="text-slate-400">ORB / DRB</div><div>{{ $p->offensive_rebounds ?? '—' }} / {{ $p->defensive_rebounds ?? '—' }}</div></div>
                                            <div><div class="text-slate-400">TRB%</div><div>{{ $p->total_rebounds_percentage !== null ? round($p->total_rebounds_percentage, 1) : '—' }}</div></div>
                                            <div><div class="text-slate-400">AST%</div><div>{{ $p->assists_percentage !== null ? round($p->assists_percentage, 1) : '—' }}</div></div>
                                            <div><div class="text-slate-400">STL%</div><div>{{ $p->steals_percentage !== null ? round($p->steals_percentage, 1) : '—' }}</div></div>
                                            <div><div class="text-slate-400">BLK%</div><div>{{ $p->blocks_percentage !== null ? round($p->blocks_percentage, 1) : '—' }}</div></div>
                                            <div><div class="text-slate-400">TOV%</div><div>{{ $p->turnovers_percentage !== null ? round($p->turnovers_percentage, 1) : '—' }}</div></div>
                                            <div class="col-span-2"><div class="text-slate-400">Stocks/G</div><div>{{ ($p->games && $p->steals !== null && $p->blocked_shots !== null) ? round(($p->steals + $p->blocked_shots) / $p->games, 1) : '—' }}</div></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>

                    {{-- Desktop expanded row --}}
                    <tr id="{{ $detailId }}-desktop" class="hidden max-md:hidden bg-slate-900/95">
                        <td colspan="17" class="px-4 py-4">
                            <div class="flex justify-center">
                                <div class="w-full max-w-5xl rounded-2xl border border-slate-700 bg-slate-800/70 px-6 py-6 text-center shadow-lg">
                                    <div class="grid gap-6 md:grid-cols-3">
                                        <div class="rounded-xl border border-slate-700 bg-slate-900/60 p-4">
                                            <h4 class="mb-3 text-xs font-semibold uppercase tracking-[0.18em] text-sky-300">Overview</h4>
                                            <div class="space-y-2 text-sm text-slate-200">
                                                <div><span class="text-slate-400">Player:</span> {{ $p->player_name }}</div>
                                                <div><span class="text-slate-400">From:</span> {{ $p->from_team }}</div>
                                                <div><span class="text-slate-400">Position:</span> {{ $p->position ?? '—' }}</div>
                                                <div><span class="text-slate-400">Games:</span> {{ $p->games ?? '—' }}</div>
                                                <div><span class="text-slate-400">Minutes:</span> {{ $p->minutes ?? '—' }}</div>
                                                <div><span class="text-slate-400">MPG:</span> {{ $p->mpg ?? '—' }}</div>
                                                <div><span class="text-slate-400">PF:</span> {{ $p->personal_fouls ?? '—' }}</div>
                                            </div>
                                        </div>

                                        <div class="rounded-xl border border-slate-700 bg-slate-900/60 p-4">
                                            <h4 class="mb-3 text-xs font-semibold uppercase tracking-[0.18em] text-sky-300">Shooting Volume</h4>
                                            <div class="grid grid-cols-2 gap-3 text-sm text-slate-200">
                                                <div><div class="text-slate-400">FGA/G</div><div>{{ ($p->games && $p->field_goals_attempted !== null) ? round($p->field_goals_attempted / $p->games, 1) : '—' }}</div></div>
                                                <div><div class="text-slate-400">2PT</div><div>{{ $p->field_goals_made ?? '—' }}/{{ $p->field_goals_attempted ?? '—' }}</div></div>
                                                <div><div class="text-slate-400">3PA/G</div><div>{{ ($p->games && $p->three_pointers_attempted !== null) ? round($p->three_pointers_attempted / $p->games, 1) : '—' }}</div></div>
                                                <div><div class="text-slate-400">3PT</div><div>{{ $p->three_pointers_made ?? '—' }}/{{ $p->three_pointers_attempted ?? '—' }}</div></div>
                                                <div><div class="text-slate-400">FTA/G</div><div>{{ ($p->games && $p->free_throws_attempted !== null) ? round($p->free_throws_attempted / $p->games, 1) : '—' }}</div></div>
                                                <div><div class="text-slate-400">FT</div><div>{{ $p->free_throws_made ?? '—' }}/{{ $p->free_throws_attempted ?? '—' }}</div></div>
                                                <div class="col-span-2"><div class="text-slate-400">2PT</div><div>{{ $p->two_pointers_made ?? '—' }}/{{ $p->two_pointers_attempted ?? '—' }}</div></div>
                                            </div>
                                        </div>

                                        <div class="rounded-xl border border-slate-700 bg-slate-900/60 p-4">
                                            <h4 class="mb-3 text-xs font-semibold uppercase tracking-[0.18em] text-sky-300">Percentages & Advanced</h4>
                                            <div class="grid grid-cols-2 gap-3 text-sm text-slate-200">
                                                <div><div class="text-slate-400">FG%</div><div>{{ $p->field_goals_percentage !== null ? round($p->field_goals_percentage, 1) : '—' }}</div></div>
                                                <div><div class="text-slate-400">eFG%</div><div>{{ $p->effective_field_goals_percentage !== null ? round($p->effective_field_goals_percentage, 1) : '—' }}</div></div>
                                                <div><div class="text-slate-400">2PT%</div><div>{{ $p->two_pointers_percentage !== null ? round($p->two_pointers_percentage, 1) : '—' }}</div></div>
                                                <div><div class="text-slate-400">3PT%</div><div>{{ $p->three_pointers_percentage !== null ? round($p->three_pointers_percentage, 1) : '—' }}</div></div>
                                                <div><div class="text-slate-400">FT%</div><div>{{ $p->free_throws_percentage !== null ? round($p->free_throws_percentage, 1) : '—' }}</div></div>
                                                <div><div class="text-slate-400">TS%</div><div>{{ $p->true_shooting_percentage !== null ? round($p->true_shooting_percentage, 1) : '—' }}</div></div>
                                                <div><div class="text-slate-400">PER</div><div>{{ $p->player_efficiency_rating !== null ? round($p->player_efficiency_rating, 1) : '—' }}</div></div>
                                                <div><div class="text-slate-400">USG%</div><div>{{ $p->usage_rate_percentage !== null ? round($p->usage_rate_percentage, 1) : '—' }}</div></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-6 flex justify-center">
                                        <div class="w-full max-w-3xl rounded-xl border border-slate-700 bg-slate-900/60 p-4">
                                            <h4 class="mb-3 text-xs font-semibold uppercase tracking-[0.18em] text-sky-300">Impact & Rates</h4>
                                            <div class="grid grid-cols-2 gap-3 text-sm text-slate-200 md:grid-cols-3">
                                                <div><div class="text-slate-400">ORB / DRB</div><div>{{ $p->offensive_rebounds ?? '—' }} / {{ $p->defensive_rebounds ?? '—' }}</div></div>
                                                <div><div class="text-slate-400">TRB%</div><div>{{ $p->total_rebounds_percentage !== null ? round($p->total_rebounds_percentage, 1) : '—' }}</div></div>
                                                <div><div class="text-slate-400">AST%</div><div>{{ $p->assists_percentage !== null ? round($p->assists_percentage, 1) : '—' }}</div></div>
                                                <div><div class="text-slate-400">STL%</div><div>{{ $p->steals_percentage !== null ? round($p->steals_percentage, 1) : '—' }}</div></div>
                                                <div><div class="text-slate-400">BLK%</div><div>{{ $p->blocks_percentage !== null ? round($p->blocks_percentage, 1) : '—' }}</div></div>
                                                <div><div class="text-slate-400">TOV%</div><div>{{ $p->turnovers_percentage !== null ? round($p->turnovers_percentage, 1) : '—' }}</div></div>
                                                <div class="col-span-2 md:col-span-3">
                                                    <div class="text-slate-400">Stocks/G</div>
                                                    <div>{{ ($p->games && $p->steals !== null && $p->blocked_shots !== null) ? round(($p->steals + $p->blocked_shots) / $p->games, 1) : '—' }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="18" class="py-20 text-left text-gray-500" style="padding: 25px;">
                            <div class="flex flex-col items-left justify-left gap-3">
                                <div class="text-lg font-semibold">No portal players found</div>
                                <div class="text-sm text-gray-400">Try adjusting your filters</div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>
</body>
<script>
    function togglePlayerDetails(detailId) {
        const mobileRow = document.getElementById(`${detailId}-mobile`);
        const desktopRow = document.getElementById(`${detailId}-desktop`);
        const icon = document.getElementById(`icon-${detailId}`);

        if (mobileRow) {
            mobileRow.classList.toggle('hidden');
        }

        if (desktopRow) {
            desktopRow.classList.toggle('hidden');
        }

        const isHidden =
            (!mobileRow || mobileRow.classList.contains('hidden')) &&
            (!desktopRow || desktopRow.classList.contains('hidden'));

        if (icon) {
            icon.textContent = isHidden ? '▸' : '▾';
        }
    }
</script>

<script>
    function toggleFilterPopup(id) {
        document.querySelectorAll('[id^="filter-"]').forEach(el => {
            if (el.id !== id) el.classList.add('hidden');
        });

        document.getElementById(id).classList.toggle('hidden');
    }

    document.addEventListener('click', function (e) {
        if (!e.target.closest('th.relative')) {
            document.querySelectorAll('[id^="filter-"]').forEach(el => el.classList.add('hidden'));
        }
    });
</script>

@endsection