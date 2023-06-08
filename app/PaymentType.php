<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class PaymentType extends Model
{
    use LogsActivity;

    public $primaryKey = "payment_type_id";
    public $timestamps = false;

    protected $fillable = ['name', 'required_field', 'default_ratesheet_id'];

    public function getActivityLogOptions() : LogOptions {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
