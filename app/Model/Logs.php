<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Logs extends Model
{
    protected $connection = 'wxrrd_logs';

    protected $table = 'logs_201606';

    protected $guarded = ['id'];

    protected function bootIfNotBooted(){
        $this->table = 'logs_'.date('Ym', time());

        parent::bootIfNotBooted();
    }
}
