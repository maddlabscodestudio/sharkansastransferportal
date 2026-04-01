<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    protected $fillable = [
        'sportsdataio_player_id',
        'first_name',
        'last_name',
        'full_name',
        'team_key',
        'position',
        'height',
        'weight',
        'raw_payload',
    ];

    protected $casts = [
        'raw_payload' => 'array',
    ];
}
