<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class Employee extends Model
{
    use LogsActivity;

    public $timestamps = false;
    public $primaryKey = "employee_id";

    protected $fillable = [
        'employee_id',
        'active',
        'company_name',
        'contact_id',
        'delivery_commission',
        'dob',
        'drivers_license_expiration_date',
        'drivers_license_number',
        'employee_number',
        'insurance_expiration_date',
        'insurance_number',
        'is_driver',
        'license_plate_expiration_date',
        'license_plate_number',
        'pickup_commission',
        'sin',
        'start_date',
        'user_id'
    ];

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
