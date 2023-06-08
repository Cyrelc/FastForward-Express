<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class DriverChargeback extends Model
{
    use LogsActivity;

    public $primaryKey = 'driver_chargeback_id';
    public $timestamps = false;

    protected $fillable = ['manifest_id','chargeback_id'];

    public function getActivityLogOptions() : LogOptions {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
