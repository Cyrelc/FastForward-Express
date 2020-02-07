<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class PaymentType extends Model
{
    use LogsActivity;

    public $primaryKey = "payment_type_id";
    public $timestamps = false;

    protected $fillable = ['name', 'required_field', 'default_ratesheet_id'];

    protected static $logFillable = true;
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;
}
