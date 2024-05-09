<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Conditional extends Model {
    use LogsActivity;

    public $primaryKey = 'conditional_id';
    public $timestamps = true;

    protected $fillable = [
        'action',
        'equation_string',
        'human_readable',
        'json_logic',
        'name',
        'original_equation_string',
        'priority',
        'ratesheet_id',
        'type',
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
