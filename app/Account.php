<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    public $primaryKey = "account_id";
    public $timestamps = false;

    protected $fillable = ['rate_type_id', 'parent_account_id', 'billing_address_id', 'shipping_address_id', 'account_number', 'invoice_interval', "invoice_comment", "stripe_id", "name", "start_date", "send_bills", "is_master", "gets_discount", "discount", "gst_exempt", "charge_interest", "fuel_surcharge", "can_be_parent", "custom_field", "uses_custom_field", "active"];

    public function contacts() {
        return $this->belongsToMany('App\Contact', 'account_contacts');
    }

    public function rate_types() {
        return $this->belongsTo('App\RateType');
    }
}
