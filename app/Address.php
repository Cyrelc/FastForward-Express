<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Address extends Model
{
    use HasFactory, LogsActivity;

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
