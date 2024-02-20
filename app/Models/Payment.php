<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Payment extends Model
{
    use LogsActivity, SoftDeletes;

    public $primaryKey = "payment_id";
    public $timestamps = false;

    protected $fillable = [
        'account_id',
        'amount',
        'comment',
        'date',
        'invoice_id',
        'payment_intent_id',
        'payment_intent_status',
        'payment_type_id',
        'reference_value',
    ];

    public function IsStripeTransaction() {
        return $this->payment_intent_id != null;
    }

    public function getActivityLogOptions() : LogOptions {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
