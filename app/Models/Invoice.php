<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Invoice extends Model {
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

    public function account() : BelongsTo {
        return $this->belongsTo(Account::class, 'account_id', 'account_id');
    }

    public function bills() {
        return Bill::join('charges', 'charges.bill_id', 'bills.bill_id')
            ->join('line_items', 'line_items.charge_id', 'charges.charge_id')
            ->where('line_items.invoice_id', $this->invoice_id)
            ->select('bills.*')
            ->distinct()
            ->get();
    }

    public function line_items() : hasMany {
        return $this->hasMany(LineItem::class, 'invoice_id', 'invoice_id');
    }

    public function getActivityLogOptions() : LogOptions {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
