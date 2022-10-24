<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Laravel\Cashier\Billable;
use Spatie\Activitylog\Traits\LogsActivity;

class Account extends Model
{
    use Billable;
    use LogsActivity;

    public $primaryKey = 'account_id';
    public $timestamps = true;

    protected $fillable = [
        'active',
        'account_balance',
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
        'show_invoice_line_items',
        'start_date',
    ];

    protected static $logFillable = true;
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;

    public function contacts() {
        return $this->belongsToMany('App\Contact', 'account_users');
    }

    public function ratesheet() {
        return $this->belongsTo('App\Ratesheet');
    }

    public function parentAccount() {
        return $this->belongsTo(Account::class, 'parent_account_id');
    }

    //editable fields
    public static $accountingFields = ['account_balance'];
    public static $advancedFields = ['account_number', 'parent_account_id', 'start_date', 'ratesheet_id', 'min_invoice_amount', 'discount', 'gst_exempt', 'can_be_parent', 'send_bills'];
    public static $basicFields = ['name', 'account_id', 'billing_address_id', 'shipping_address_id'];
    public static $invoicingFields = ['custom_field', 'invoice_comment', 'invoice_interval', 'invoice_sort_order', 'is_custom_field_mandatory', 'send_email_invoices', 'send_paper_invoices', 'show_invoice_line_items'];
    // public static $invoicingFields = ['custom_field', 'invoice_comment', 'invoice_interval', 'invoice_separately_from_parent', 'invoice_sort_order', 'is_custom_field_mandatory', 'send_email_invoices', 'send_paper_invoices'];

    /**
     * Readonly fields - we must distinguish because some fields must be *visible* to all users, but are not *editable* by those users
     */
    public static $readOnlyFields = ['active', 'account_number', 'parent_account_id', 'created_at', 'updated_at'];
}
