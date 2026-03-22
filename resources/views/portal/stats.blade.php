<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sharkansas Portal Stats</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-900">
<div class="max-w-7xl mx-auto p-6">
    <div class="flex items-end justify-between gap-4 flex-wrap">
        <div>
            <h1 class="text-2xl font-bold">Portal Stats</h1>
            <p class="text-sm text-gray-600">Stored player stats for portal players. No live API hits on page load.</p>
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

            <button class="bg-black text-white rounded px-4 py-2 text-sm">Filter</button>
        </form>
    </div>

    <div class="mt-6 overflow-x-auto bg-white border rounded">
        <table class="w-full text-sm">
            <thead class="bg-gray-100 text-left">
            <tr>
                <th class="p-3">Player</th>
                <th class="p-3">From Team</th>
                <th class="p-3">Stats Team</th>
                <th class="p-3">Pos</th>
                <th class="p-3">Games</th>
                <th class="p-3">PPG</th>
                <th class="p-3">RPG</th>
                <th class="p-3">APG</th>
                <th class="p-3">TS%</th>
                <th class="p-3">Synced</th>
            </tr>
            </thead>
            <tbody>
            @forelse($players as $p)
                <tr class="border-t">
                    <td class="p-3 font-medium">{{ $p->player_name }}</td>
                    <td class="p-3">{{ $p->from_team }}</td>
                    <td class="p-3">
                        @if($p->stats_team_name || $p->stats_team_key)
                            {{ $p->stats_team_name ?? '—' }}
                            @if($p->stats_team_key)
                                <span class="text-gray-500">({{ $p->stats_team_key }})</span>
                            @endif
                        @else
                            <span class="text-gray-400">No match yet</span>
                        @endif
                    </td>
                    <td class="p-3">{{ $p->position ?? '—' }}</td>
                    <td class="p-3">{{ $p->games ?? '—' }}</td>
                    <td class="p-3">
                        @if(!empty($p->games) && !empty($p->points))
                            {{ round($p->points / $p->games, 1) }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="p-3">
                        @if(!empty($p->games) && !empty($p->rebounds))
                            {{ round($p->rebounds / $p->games, 1) }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="p-3">
                        @if(!empty($p->games) && !empty($p->assists))
                            {{ round($p->assists / $p->games, 1) }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="p-3">
                        {{ $p->true_shooting_percentage ? round($p->true_shooting_percentage, 1) : '—' }}
                    </td>
                    <td class="p-3 text-gray-700">
                        {{ $p->synced_at ?? '—' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="p-6 text-center text-gray-500">No portal players found.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
</body>
</html>