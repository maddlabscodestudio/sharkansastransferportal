<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayerSeasonStat extends Model
{
    protected $fillable = [
        'player_name',
        'team_key',
        'team_name',
        'sportsdataio_player_id',
        'sportsdataio_team_id',
        'sportsdataio_stat_id',
        'season',
        'season_type',
        'position',
        'games',
        'minutes',
        'points',
        'rebounds',
        'assists',
        'steals',
        'blocked_shots',
        'turnovers',
        'field_goals_percentage',
        'three_pointers_percentage',
        'free_throws_percentage',
        'true_shooting_percentage',
        'player_efficiency_rating',
        'usage_rate_percentage',
        'source_updated_at',
        'synced_at',
        'raw_payload',
        'field_goals_made',
        'field_goals_attempted',
        'two_pointers_made',
        'two_pointers_attempted',
        'two_pointers_percentage',
        'three_pointers_made',
        'three_pointers_attempted',
        'free_throws_made',
        'free_throws_attempted',
        'offensive_rebounds',
        'defensive_rebounds',
        'personal_fouls',
        'effective_field_goals_percentage',
        'offensive_rebounds_percentage',
        'defensive_rebounds_percentage',
        'total_rebounds_percentage',
        'assists_percentage',
        'steals_percentage',
        'blocks_percentage',
        'turnovers_percentage',
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'source_updated_at' => 'datetime',
        'synced_at' => 'datetime',
    ];
}