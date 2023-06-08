<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class AccountInvoiceSortOrder extends Model
{
    use LogsActivity;

    public $primaryKey = 'account_invoice_sort_order_id';
    public $timestamps = false;

    protected $table = 'account_invoice_sort_order';

    protected $fillable = [
        'invoice_sort_option_id',
        'subtotal_by',
        'priority',
        'account_id',
    ];

    public function getActivityLogOptions() : LogOptions {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
