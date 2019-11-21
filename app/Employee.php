<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class Employee extends Model
{
    use LogsActivity;

    public $timestamps = false;
    public $primaryKey = "employee_id";

    protected $fillable = ['employee_id', 'contact_id', 'user_id', 'employee_number', 'start_date', 'sin', 'dob', 'active'];

    protected static $logFillable = true;
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;

    public function contact() {
        return $this->belongsTo('App\Contact');
    }

    public function contacts() {
        return $this->belongsToMany('App\Contact', 'employee_emergency_contacts');
    }
}
