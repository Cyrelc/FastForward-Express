<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class Account extends Model
{
    use LogsActivity;

    public $primaryKey = 'account_id';
    public $timestamps = false;

    protected $fillable = [
        'active',
        'account_number',
        'billing_address_id',
        'can_be_parent',
        'custom_field',
        'discount',
        'gst_exempt',
        'invoice_interval',
        'invoice_comment',
        'invoice_sort_order',
        'min_invoice_amount',
        'name',
        'parent_account_id',
        'ratesheet_id',
        'send_bills',
        'send_email_invoices',
        'send_paper_invoices',
        'shipping_address_id',
        'start_date',
        'use_parent_ratesheet'
    ];

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
