<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class Ratesheet extends Model
{
    public $primaryKey = "ratesheet_id";

    protected $fillable = ['name', 'use_internal_zones_calc', 'delivery_types', 'weight_rates', 'zone_rates', 'map_zones'];
    public $timestamps = false;

    public function Accounts() {
        return $this->hasMany('App\Account');
    }
    protected static $logFillable = true;
    protected static $logOnlyDirty = true;
}
