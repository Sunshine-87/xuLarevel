<?php

namespace App\Model\Dota;

use Illuminate\Database\Eloquent\Model;

class Heros extends Model
{
    protected $connection = 'dota';

    protected $table = 'heros';

    protected $guarded = ['id'];
}
