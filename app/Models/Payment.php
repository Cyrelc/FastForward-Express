<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

use App\Models\PaymentType;

class Payment extends Model {
    use HasFactory, LogsActivity, SoftDeletes;

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
        'stripe_object_type',
        'stripe_payment_intent_id',
        'stripe_refund_id',
        'stripe_status',
    ];


    public function payment_type() {
        return $this->hasOne(PaymentType::class, 'payment_type_id', 'payment_type_id');
    }

    public function isStripeTransaction() {
        return $this->stripe_payment_intent_id != null;
    }

    public function getActivityLogOptions() : LogOptions {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
