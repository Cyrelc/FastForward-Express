<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class PhoneNumber extends Model
{
    use LogsActivity;

    public $primaryKey = "phone_number_id";
    public $timestamps = false;

    protected $fillable = ['phone_number', 'extension_number', 'is_primary', 'type', 'contact_id'];

    public function getActivityLogOptions() : LogOptions {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
