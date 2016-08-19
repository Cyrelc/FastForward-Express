<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Bill extends Model
{
    public $primaryKey = "bill_id";
    public $timestamps = false;
}
