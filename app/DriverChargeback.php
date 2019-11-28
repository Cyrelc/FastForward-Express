<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class DriverChargeback extends Model
{
    use LogsActivity;

    public $primaryKey = 'driver_chargeback_id';
    public $timestamps = false;

    protected $fillable = ['manifest_id','chargeback_id'];

    protected static $logFillable = true;
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;
}
