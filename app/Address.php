<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Address extends Model
{
    use LogsActivity;

    public $primaryKey = "address_id";
    public $timestamps = false;

    protected $fillable = [
        'contact_id',
        'formatted',
        'is_mall',
        'is_primary',
        'lat',
        'lng',
        'name',
        'place_id'
    ];

    public function getActivityLogOptions() : LogOptions {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getFillable() {
        return $this->fillable;
    }
}
