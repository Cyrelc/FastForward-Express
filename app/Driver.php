<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    public $primaryKey = "driver_id";
    public $timestamps = false;

    protected $fillable = ['driver_id', 'contact_id', 'user_id', 'driver_number','stripe_id', 'start_date', 'drivers_license_number', 'license_expiration', 'license_plate_number', 'license_plate_expiration', 'insurance_number', 'insurance_expiration', 'sin', 'dob', 'active', 'pickup_commission', 'delivery_commission'];

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
