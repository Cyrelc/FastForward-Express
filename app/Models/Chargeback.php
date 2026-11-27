<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Chargeback extends Model
{
    use LogsActivity;

    public $primaryKey = "chargeback_id";

    protected $fillable = [
        'employee_id',
        'manifest_id',
        'amount',
        'gl_code',
        'name',
        'description',
        'continuous',
        'count_remaining',
        'start_date',
        'bill_id',
        'source_chargeback_id',
    ];

    public function getActivityLogOptions() : LogOptions {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
