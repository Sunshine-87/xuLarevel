<?php

namespace App\Model\Dota;

use Illuminate\Database\Eloquent\Model;

class Items extends Model
{
    protected $connection = 'dota';

    protected $table = 'items';

    protected $guarded = ['id'];
}
