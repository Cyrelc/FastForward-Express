<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class Bill extends Model
{
    use LogsActivity;
    
    public $primaryKey = "bill_id";
    public $timestamps = false;

    protected $fillable = [
        'amount',
        'bill_number',
        'charge_account_id',
        'charge_reference_value',
        'chargeback_id',
        'created_at',
        'delivery_account_id',
        'delivery_address_id',
        'delivery_driver_commission',
        'delivery_driver_id',
        'delivery_manifest_id',
        'delivery_reference_value',
        'delivery_type',
        'description',
        'incomplete_fields',
        'interliner_cost',
        'interliner_cost_to_customer',
        'interliner_id',
        'interliner_reference_value',
        'internal_comments',
        'invoice_id',
        'is_min_weight_size',
        'is_pallet',
        'packages',
        'payment_id',
        'payment_type_id',
        'percentage_complete',
        'pickup_account_id',
        'pickup_address_id',
        'pickup_driver_commission',
        'pickup_driver_id',
        'pickup_manifest_id',
        'pickup_reference_value',
        'repeat_interval',
        'skip_invoicing',
        'time_pickup_scheduled',
        'time_delivery_scheduled',
        'time_call_received',
        'time_dispatched',
        'time_picked_up',
        'time_delivered',
        'updated_at',
        'use_imperial'
    ];

    protected static $logFillable = true;
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;

    public function chargeAccount() {
        return $this->belongsTo(Account::class, 'charge_account_id');
    }

    //editable fields
    public static $basicFields = [
        'delivery_account_id',
        'delivery_address_id',
        'delivery_reference_value',
        'delivery_type',
        'description',
        'is_min_weight_size',
        'is_pallet',
        'packages',
        'pickup_account_id',
        'pickup_address_id',
        'pickup_reference_value',
        'time_pickup_scheduled',
        'time_delivery_scheduled',
        'use_imperial'
    ];

    public static $dispatchFields = [
        'bill_number',
        'delivery_driver_commission',
        'delivery_driver_id',
        'internal_comments',
        'pickup_driver_commission',
        'pickup_driver_id',
        'time_call_received',
        'time_dispatched',
        'time_picked_up',
        'time_delivered'
    ];

    public static $billingFields = [
        'amount',
        'charge_account_id',
        'charge_reference_value',
        'chargeback_id',
        'interliner_cost',
        'interliner_cost_to_customer',
        'interliner_id',
        'interliner_reference_value',
        'payment_id',
        'repeat_interval',
        'skip_invoicing',
    ];

    /**
     * Readonly fields
     */
    public static $readOnlyFields = [
        'amount',
        'bill_id',
        'charge_account_id',
        'created_at',
        'delivery_manifest_id',
        'incomplete_fields',
        'invoice_id',
        'payment_type_id',
        'percentage_complete',
        'pickup_manifest_id',
        'updated_at'
    ];
}
