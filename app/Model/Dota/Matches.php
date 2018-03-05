<?php

namespace App\Model\Dota;

use Illuminate\Database\Eloquent\Model;

class Matches extends Model
{
    protected $connection = 'dota';

    protected $table = 'matches';

    protected $guarded = ['id'];
}
