<?php

namespace App\Model\Dota;

use Illuminate\Database\Eloquent\Model;

class MatchPlayers extends Model
{
    protected $connection = 'dota';

    protected $table = 'match_players';

    protected $guarded = ['id'];
}
