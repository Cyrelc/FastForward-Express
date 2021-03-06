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
        'updated_at',
        'user_id'
    ];

    protected static $logFillable = true;
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;

    public function contact() {
        return $this->hasOne(Contact::class);
    }

    public function contacts() {
        return $this->belongsToMany('App\Contact', 'employee_emergency_contacts');
    }

    public function user() {
        return $this->belongsTo('App\User');
    }

    /**Editable fields */
    public static $advancedFields = [
        'employee_number',
        'is_driver',
        'start_date',
        'dob',
        'sin'
    ];

    public static $basicFields = [];

    public static $driverFields = [
        'drivers_license_number',
        'drivers_license_expiration_date',
        'license_plate_number',
        'license_plate_expiration_date',
        'insurance_number',
        'insurance_expiration_date',
        'pickup_commission',
        'delivery_commission',
    ];

    /**Readonly fields */
    public static $readOnlyFields = ['contact_id', 'employee_number', 'employee_id', 'drivers_license_expiration_date', 'license_plate_expiration_date', 'insurance_expiration_date', 'is_driver', 'employees.updated_at as updated_at'];
}
