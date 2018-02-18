<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Manifest extends Model
{
    public $primaryKey = "manifest_id";
    public $timestamps = false;

    protected $fillable = ['date_run', 'driver_id', 'start_date', 'end_date'];
}
