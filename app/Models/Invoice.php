<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

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
        'finalized',
        'min_invoice_amount',
        'notification_sent',
        'payment_type_id',
        'tax',
        'total_cost',
    ];

    public function getActivityLogOptions() : LogOptions {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
