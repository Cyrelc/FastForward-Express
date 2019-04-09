<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class EmployeeEmergencyContact extends Model
{
    use LogsActivity;

    public $primaryKey = 'contact_id';
    public $timestamps = false;

    protected $fillable = ['employee_id', 'contact_id', 'is_primary'];

    protected static $logFillable = true;
    protected static $logOnlyDirty =  true;
}

