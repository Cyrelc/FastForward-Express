<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class Driver extends Model
{
    use LogsActivity;

    public $primaryKey = "driver_id";
    public $timestamps = false;

    protected $fillable = ['employee_id','driver_id', 'contact_id', 'user_id', 'driver_number','stripe_id', 'start_date', 'drivers_license_number', 'license_expiration', 'license_plate_number', 'license_plate_expiration', 'insurance_number', 'insurance_expiration', 'sin', 'dob', 'active', 'pickup_commission', 'delivery_commission'];

    protected static $logFillable = true;
    protected static $logOnlyDirty = true;

    public function bills() {
        return $this->belongsToMany('App\Bill');
    }

    public function contact() {
        return $this->belongsTo('App\Contact');
    }

    public function contacts() {
        return $this->belongsToMany('App\Contact', 'driver_emergency_contacts');
    }
}
