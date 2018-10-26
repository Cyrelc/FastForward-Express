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
        'date',
        'bill_cost',
        'tax',
        'discount',
        'total_cost',
        'balance_owing'
    ];
    
    protected static $logFillable = true;
    protected static $logOnlyDirty = true;
}
