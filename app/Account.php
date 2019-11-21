<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class Account extends Model
{
    use LogsActivity;

    public $primaryKey = "account_id";
    public $timestamps = false;

    protected $fillable = ['ratesheet_id',
                    'has_parent',
                    'parent_account_id',
                    'billing_address_id',
                    'shipping_address_id',
                    'account_number',
                    'invoice_interval',
                    'invoice_comment',
                    'stripe_id',
                    'name',
                    'start_date',
                    'send_bills',
                    'send_invoices',
                    'is_master',
                    'has_discount',
                    'discount',
                    'gst_exempt',
                    'charge_interest',
                    'fuel_surcharge',
                    'can_be_parent',
                    'custom_field',
                    'uses_custom_field',
                    'active',
                    'min_invoice_amount',
                    'use_parent_ratesheet'];

    protected static $logFillable = true;
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;

    public function contacts() {
        return $this->belongsToMany('App\Contact', 'account_users');
    }

    public function rate_types() {
        return $this->belongsTo('App\RateType');
    }
}
