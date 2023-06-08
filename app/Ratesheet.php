<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Ratesheet extends Model
{
    public $primaryKey = "ratesheet_id";

    protected $fillable = ['name', 'use_internal_zones_calc', 'delivery_types', 'weight_rates', 'zone_rates', 'time_rates', 'misc_rates'];
    public $timestamps = false;

    public function Accounts() {
        return $this->hasMany('App\Account');
    }

    public function getActivityLogOptions() : LogOptions {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
