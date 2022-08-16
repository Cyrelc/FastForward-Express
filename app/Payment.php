<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Spatie\Activitylog\Traits\LogsActivity;

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
        'payment_type_id',
        'reference_value',
    ];

    protected static $logFillable = true;
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;
}
