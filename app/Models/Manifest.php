<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Manifest extends Model
{
    use LogsActivity;

    public $primaryKey = 'manifest_id';
    public $timestamps = false;

    protected $fillable = ['date_run', 'employee_id', 'start_date', 'end_date'];

    public function getActivityLogOptions() : LogOptions {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
