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
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'source_updated_at' => 'datetime',
        'synced_at' => 'datetime',
    ];
}