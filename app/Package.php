<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class Package extends Model
{
	use LogsActivity;

    public $timestamps = false;
    public $primaryKey = "package_id";

    protected $fillable = [
		'bill_id',
		'count',
    	'weight',
    	'height',
    	'width',
    	'length'
	];

	protected static $logFillable = true;
    protected static $logOnlyDirty = true;
}
