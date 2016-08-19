<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Chargeback extends Model
{
    public $primaryKey = "chargeback_id";
    public $timestamps = false;
}
