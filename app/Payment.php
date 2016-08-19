<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    public $primaryKey = "payment_id";
    public $timestamps = false;
}
