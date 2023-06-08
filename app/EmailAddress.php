<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class EmailAddress extends Model
{
    use LogsActivity;

    public $primaryKey = "email_address_id";
    public $timestamps = false;

    protected $fillable = ['email', 'is_primary', 'contact_id', 'type'];

    public function getActivityLogOptions() : LogOptions {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
