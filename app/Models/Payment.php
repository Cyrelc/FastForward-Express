<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Payment extends Model {
    use LogsActivity, SoftDeletes;

    public $primaryKey = "payment_id";
    public $timestamps = false;
    protected $dates = ['deleted_at'];

    protected $fillable = [
        'account_id',
        'amount',
        'comment',
        'date',
        'invoice_id',
        'payment_type_id',
        'receipt_url',
        'reference_value',
        'stripe_id',
        'stripe_object_type',
        'stripe_status',
    ];

    public function IsStripeTransaction() {
        return $this->stripe_id != null;
    }

    public function getActivityLogOptions() : LogOptions {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
