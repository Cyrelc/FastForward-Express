<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class Selection extends Model
{
	use LogsActivity;

    protected $fillable = [
    	'selection_id',
    	'name',
    	'value',
    	'type'
	];
	
	protected static $logFillable = true;
	protected static $logOnlyDirty = true;
	protected static $submitEmptyLogs = false;
}
