<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DriverChargeback extends Model
{
    public $primaryKey = 'driver_chareback_id';
    public $timestamps = false;

    protected $fillable = ['manifest_id','chargeback_id'];
}
