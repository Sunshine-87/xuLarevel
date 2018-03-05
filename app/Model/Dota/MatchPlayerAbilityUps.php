<?php

namespace App\Model\Dota;

use Illuminate\Database\Eloquent\Model;

class MatchPlayerAbilityUps extends Model
{
    protected $connection = 'dota';

    protected $table = 'match_player_abilityUps';

    protected $guarded = ['id'];
}
