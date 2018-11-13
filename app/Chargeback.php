<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class Chargeback extends Model
{
    use LogsActivity;

    public $primaryKey = "chargeback_id";
    public $timestamps = false;

    protected $fillable = [
        'employee_id',
        'manifest_id',
        'amount',
        'gl_code',
        'name',
        'description',
        'continuous',
        'count_remaining',
        'start_date'
    ];

    protected static $logFillable = true;
    protected static $logOnlyDirty = true;
}
