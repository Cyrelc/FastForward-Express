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
        // 'invoice_separately_from_parent',
        'invoice_sort_order',
        'is_custom_field_mandatory',
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

    //editable fields
    public static $accountingFields = ['account_balance'];
    public static $advancedFields = ['account_number', 'parent_account_id', 'start_date', 'ratesheet_id', 'min_invoice_amount', 'discount', 'gst_exempt', 'can_be_parent', 'send_bills', 'use_parent_ratesheet'];
    public static $basicFields = ['name', 'account_id', 'billing_address_id', 'shipping_address_id'];
    public static $invoicingFields = ['custom_field', 'invoice_comment', 'invoice_interval', 'invoice_sort_order', 'is_custom_field_mandatory', 'send_email_invoices', 'send_paper_invoices'];
    // public static $invoicingFields = ['custom_field', 'invoice_comment', 'invoice_interval', 'invoice_separately_from_parent', 'invoice_sort_order', 'is_custom_field_mandatory', 'send_email_invoices', 'send_paper_invoices'];

    /**
     * Readonly fields - we must distinguish because some fields must be *visible* to all users, but are not *editable* by those users
     */
    public static $readOnlyFields = ['account_number', 'parent_account_id'];
}
