<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class MonerisTransaction extends Model {
    use LogsActivity;

    public $primaryKey = 'moneris_transaction_id';
    public $timestamps = true;

    protected $fillable = [
        'credit_card_id',
        'invoice_id',
        'order_id',
        'type',
        'user_id'
    ];

    public function getActivityLogOptions() : LogOptions {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
