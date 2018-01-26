<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class OrderRefund extends Model
{
    protected $table = 'order_refund';

    protected $guarded = ['id'];
}
