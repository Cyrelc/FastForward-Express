<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class Bill extends Model
{
    use LogsActivity;
    
    public $primaryKey = "bill_id";
    public $timestamps = false;

    protected $fillable = [	'chargeback_id',
                            'payment_id',
                            'charge_account_id',
    						'pickup_account_id',
    						'delivery_account_id',
                            'pickup_address_id',
                            'delivery_address_id',
                            'charge_reference_value',
                            'pickup_reference_value',
                            'delivery_reference_value',
    						'pickup_driver_id',
    						'delivery_driver_id',
    						'pickup_driver_commission',
    						'delivery_driver_commission',
                            'interliner_id',
                            'interliner_reference_value',
                            'interliner_cost',
                            'interliner_cost_to_customer',
                            'skip_invoicing',
    						'bill_number',
    						'amount',
    						'date',
                            'description',
                            'delivery_type',
                            'time_pickup_scheduled',
                            'time_delivery_scheduled',
                            'time_call_received',
                            'time_dispatched',
                            'time_picked_up',
                            'time_delivered',
                            'percentage_complete'
                        ];

    protected static $logFillable = true;
    protected static $logOnlyDirty = true;
}
