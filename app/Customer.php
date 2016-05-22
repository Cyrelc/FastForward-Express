<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model {
    protected $table = 'customer';

    protected $fillable = [
        'company_name',
        'address', 'postal_code',
        'bill_address', 'bill_postal_code',
        'contact_name', 'phone_nums', 'email',
        'parent_id', 'rate_type_id', 'invoice_interval_id',
        //Booleans
        'autonumber_bills', 'has_reference_field', 'tax_exempt', 'apply_interest',

        'driver_comm_id', 'comm_id'

        //Missing:
        //Discount
    ];

    public function parentCompany() {
        return $this->belongsTo('App\Customer', 'id', 'parent_id');
    }

    public function rateType() {
        return $this->hasOne('App\RateType', 'id', 'rate_type_id');
    }

    public function invoiceInterval() {
        return $this->hasOne('App\InvoiceInterval', 'id', 'invoice_interval_id');
    }

    public function getPhoneNums() {
        return unserialize($this->phone_nums);
    }

    public function hasCommission() {
        return !!($this->comm_id);
    }

    public function hasDriverCommission() {
        return !!($this->driver_comm_id);
    }

    public function getDriverComm() {
        return $this->hasOne('App\Driver', 'id', 'driver_comm_id');
    }
}
