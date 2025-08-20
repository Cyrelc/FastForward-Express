<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Bill extends Model
{
    use LogsActivity;

    public $primaryKey = "bill_id";
    public $timestamps = true;

    protected $fillable = [
        'bill_number',
        'created_by',
        'delivery_account_id',
        'delivery_address_id',
        'delivery_driver_commission',
        'delivery_driver_id',
        'delivery_manifest_id',
        'delivery_person_name',
        'delivery_reference_value',
        'delivery_type',
        'description',
        'incomplete_fields',
        'interliner_cost',
        'interliner_id',
        'interliner_reference_value',
        'internal_comments',
        'is_min_weight_size',
        'is_pallet',
        'packages',
        'percentage_complete',
        'pickup_account_id',
        'pickup_address_id',
        'pickup_driver_commission',
        'pickup_driver_id',
        'pickup_manifest_id',
        'pickup_person_name',
        'pickup_reference_value',
        'proof_of_delivery_required',
        'repeat_interval',
        'skip_invoicing',
        'time_pickup_scheduled',
        'time_delivery_scheduled',
        'time_call_received',
        'time_dispatched',
        'time_picked_up',
        'time_delivered',
        'use_imperial'
    ];

    //editable fields
    public static $basicFields = [
        'delivery_account_id',
        'delivery_address_id',
        'delivery_reference_value',
        'delivery_type',
        'description',
        'is_min_weight_size',
        'is_pallet',
        'is_template',
        'packages',
        'pickup_account_id',
        'pickup_address_id',
        'pickup_reference_value',
        'proof_of_delivery_required',
        'time_pickup_scheduled',
        'time_delivery_scheduled',
        'use_imperial'
    ];

    public static $dispatchFields = [
        'bill_number',
        'delivery_driver_commission',
        'delivery_driver_id',
        'delivery_person_name',
        'internal_comments',
        'pickup_driver_commission',
        'pickup_driver_id',
        'pickup_person_name',
        'time_call_received',
        'time_dispatched',
        'time_picked_up',
        'time_delivered',
        'time_ten_foured'
    ];

    public static $driverFields = [
        'time_delivered',
        'time_picked_up',
        'time_ten_foured'
    ];

    public static $billingFields = [
        'interliner_cost',
        'interliner_id',
        'interliner_reference_value',
        'repeat_interval',
        'skip_invoicing',
    ];

    /**
     * Readonly fields
     */
    public static $readOnlyFields = [
        'bill_id',
        'created_at',
        'selections.name as delivery_type_friendly',
        'incomplete_fields',
        'percentage_complete',
        'updated_at'
    ];

    public function charges() {
        return $this->hasMany(Charge::class, 'bill_id');
    }

    public function delivery_type_name() {
        return Selection::where('value', $this->delivery_type)->first()->name;
    }

    public function pickup_address() {
        return $this->hasOne(Address::class, 'address_id', 'pickup_address_id');
    }

    public function pickup_employee() {
        return $this->hasOne(Employee::class, 'employee_id', 'pickup_driver_id');
    }

    public function delivery_address() {
        return $this->hasOne(Address::class, 'address_id', 'delivery_address_id');
    }

    public function delivery_employee() {
        return $this->hasOne(Employee::class, 'employee_id', 'delivery_driver_id');
    }

    public function line_items() {
        return $this->hasManyThrough(LineItem::class, Charge::class, 'bill_id', 'charge_id');
    }

    public function getActivityLogOptions() : LogOptions {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->dontLogIfAttributesChangedOnly(['percentage_complete']);
    }
}
