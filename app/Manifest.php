<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class Manifest extends Model
{
    use LogsActivity;

    public $primaryKey = "manifest_id";
    public $timestamps = false;

    protected $fillable = ['date_run', 'driver_id', 'start_date', 'end_date'];

    protected static $logFillable = true;
}
