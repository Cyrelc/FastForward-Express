<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\ActivityLog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Charge extends Model {
    use LogsActivity;

    public $primaryKey = 'charge_id';
    public $timestamps = true;

    protected $fillable = [
        'charge_account_id',
        'bill_id',
        'charge_reference_value',
        'charge_type_id',
        'charge_employee_id'
    ];

    public function lineItems() {
        return $this->hasMany(LineItem::class, 'charge_id');
    }

    public function bill() {
        return $this->belongsTo(Bill::class, 'bill_id');
    }

    public function account() {
        return $this->belongsTo(Account::class, 'charge_account_id');
    }

    public function getActivityLogOptions() : LogOptions {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
