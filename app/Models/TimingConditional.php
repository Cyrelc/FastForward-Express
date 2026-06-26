<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class TimingConditional extends Model {
    use LogsActivity;

    public $primaryKey = 'timing_conditional_id';
    public $timestamps = true;

    protected $fillable = [
        'action',
        'continue_on',
        'human_readable',
        'json_logic',
        'name',
        'priority',
        'ratesheet_id',
        'value',
        'value_type'
    ];

    public function getActivityLogOptions() : LogOptions {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->dontLogIfAttributesChangedOnly(['percentage_complete']);
    }
}

?>
