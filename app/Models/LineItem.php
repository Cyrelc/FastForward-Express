<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
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
        'pickup_driver_id',
        'price',
        'type'
    ];

    public function charge() : belongsTo {
        return $this->belongsTo(Charge::class, 'charge_id');
    }

    public function invoice() : belongsTo {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function typeName() {
        return Selection::where('value', $this->type)->firstOrFail()->name;
    }

    public function getActivityLogOptions() : LogOptions {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
