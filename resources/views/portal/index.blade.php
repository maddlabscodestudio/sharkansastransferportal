@extends('layouts.app')

@section('content')

<div class="max-w-6xl mx-auto p-6">
    <div class="flex items-end justify-between gap-4 flex-wrap">
        <div>
            <h1 class="text-2xl font-bold">Transfer Portal</h1>
            <p class="text-sm text-gray-600">Live transfer portal feed with source-based confidence and player stats.</p>
        </div>

        <form class="flex gap-2 flex-wrap" method="get" action="/portal">
            <select name="team" class="border rounded px-3 py-2 text-sm">
                <option value="">All teams</option>
                @foreach($teams as $t)
                    <option value="{{ $t }}" @selected(($filters['team'] ?? '') === $t)>{{ $t }}</option>
                @endforeach
            </select>

            <select name="confidence" class="border rounded px-3 py-2 text-sm">
                <option value="">All confidence</option>
                <option value="high" @selected(($filters['confidence'] ?? '') === 'high')>High</option>
                <option value="med" @selected(($filters['confidence'] ?? '') === 'med')>Med</option>
                <option value="low" @selected(($filters['confidence'] ?? '') === 'low')>Low</option>
            </select>
            <select name="flag" class="border rounded px-3 py-2 text-sm">
                <option value="">All flags</option>
                <option value="waiver" @selected(($filters['flag'] ?? '') === 'waiver')>Waiver Needed</option>
                <option value="no_eligibility" @selected(($filters['flag'] ?? '') === 'no_eligibility')>No eligibility</option>
            </select>

            <input type="number" name="limit" value="{{ $filters['limit'] ?? 50 }}" min="1" max="200"
                   class="border rounded px-3 py-2 text-sm w-24" />

            <button class="bg-black text-white rounded px-4 py-2 text-sm">Filter</button>
        </form>
    </div>

    <div class="mt-6 overflow-x-auto bg-white border rounded">
        <table class="w-full text-sm">
            <thead class="bg-gray-100 text-left">
            <tr>
                <th class="p-3">Player</th>
                <th class="p-3">From</th>
                <th class="p-3">First reported</th>
                <th class="p-3">Confidence</th>
                <th class="p-3">Reports</th>
                <th class="p-3">Reported by</th>
                <th class="p-3">Flags</th>
            </tr>
            </thead>
            <tbody>
            @foreach($events as $e)
                <tr class="border-t">
                    <td class="p-3">
                        <div class="font-medium">{{ $e->player_name }}</div>

                        @if(!empty($e->games) && $e->games > 0)
                            <div class="text-xs text-gray-600 mt-1">
                                {{ round($e->points / $e->games, 1) }} PPG •
                                {{ round($e->rebounds / $e->games, 1) }} RPG •
                                {{ round($e->assists / $e->games, 1) }} APG
                                @if(!empty($e->true_shooting_percentage))
                                    • {{ round($e->true_shooting_percentage, 1) }} TS%
                                @endif
                            </div>
                        @endif
                    </td>
                    <td class="p-3">{{ $e->from_team }}</td>
                    <td class="p-3 text-gray-700">{{ $e->first_reported_at }}</td>
                    <td class="p-3">
                        <span class="px-2 py-1 rounded border">
                            {{ strtoupper($e->confidence) }}
                        </span>
                    </td>
                    <td class="p-3">{{ $e->report_count }}</td>
                    <td class="p-3 text-gray-700">{{ str_replace(',', ', ', $e->reported_by ?? '') }}</td>
                    <td class="p-3">
                        <div class="flex gap-2 flex-wrap items-center">
                            @if($e->no_eligibility_remaining)
                                <span class="px-2 py-1 rounded border text-xs font-semibold"
                                    title="{{ $e->eligibility_note }}">
                                    NO ELIGIBILITY
                                </span>
                            @elseif($e->needs_waiver)
                                <span class="px-2 py-1 rounded border text-xs font-semibold"
                                    title="{{ $e->eligibility_note }}">
                                    WAIVER NEEDED
                                </span>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection