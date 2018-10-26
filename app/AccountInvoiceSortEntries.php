<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class AccountInvoiceSortEntries extends Model
{
    use LogsActivity;

    public $primaryKey = "account_invoice_sort_entry_id";
    public $timestamps = false;

    protected $fillable = [	'account_id',
                        'invoice_sort_option_id',
                        'priority',
                        'subtotal'
                        ];

    protected static $logFillable = true;
}
