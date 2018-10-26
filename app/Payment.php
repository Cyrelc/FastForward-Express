<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class Payment extends Model
{
    use LogsActivity;

    public $primaryKey = "payment_id";
    public $timestamps = false;

    protected $fillable = [	'account_id',
                            'invoice_id',
                            'date',
                            'amount',
                            'payment_type',
                            'reference_value',
                            'comment'
                        ];

    protected static $logFillable = true;
    protected static $logOnlyDirty = true;
}
