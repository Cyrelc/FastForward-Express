<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    public $primaryKey = "account_id";
    public $timestamps = false;

    protected $fillable = ["fuel_surcharge", 'rate_type_id', 'parent_account_id', 'billing_address_id', 'shipping_address_id', 'account_number', 'invoice_interval', "stripe_id", "name", "invoice_comment", "start_date", "send_bills", "is_master", "gst_exempt", "charge_interest", "can_be_parent", "custom_field", "active"];

    public function contacts() {
        return $this->belongsToMany('App\Contact', 'account_contacts');
    }

    public function rate_types() {
        return $this->belongsTo('App\RateType');
    }
}
