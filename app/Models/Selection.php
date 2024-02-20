<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Selection extends Model
{
	use LogsActivity;

    public $timestamps = false;

    protected $fillable = [
    	'selection_id',
    	'name',
    	'value',
    	'type'
    ];
    protected $primaryKey = 'selection_id';

    public function getActivityLogOptions() : LogOptions {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
