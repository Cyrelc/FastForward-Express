<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class Invoice extends Model
{
    use LogsActivity;

    public $primaryKey = "invoice_id";
    public $timestamps = false;

    public $fillable = [
        'account_id',
        'balance_owing',
        'bill_cost',
        'bill_start_date',
        'bill_end_date',
        'date',
        'discount',
        'min_invoice_amount',
        'tax',
        'total_cost',
    ];
    
    protected static $logFillable = true;
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;
}
