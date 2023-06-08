<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\ActivityLog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class LineItem extends Model {
    use LogsActivity;

    public $primaryKey = 'line_item_id';
    public $timestamps = true;

    protected $fillable = [
        'amendment_number',
        'charge_id',
        'chargeback_id',
        'delivery_driver_id',
        'driver_amount',
        'invoice_id',
        'name',
        'manifest_id',
        'paid',
        'pickup_driver_id',
        'price',
        'type'
    ];

    public function charge() {
        return $this->belongsTo(Charge::class, 'charge_id');
    }

    public function getActivityLogOptions() : LogOptions {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
