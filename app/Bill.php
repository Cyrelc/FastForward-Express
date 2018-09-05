<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Bill extends Model
{
    public $primaryKey = "bill_id";
    public $timestamps = false;

    protected $fillable = [	'charge_account_id',
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
                            'interliner_cost',
                            'interliner_cost_to_customer',
                            'skip_invoicing',
    						'bill_number',
    						'amount',
    						'date',
                            'description',
                            'delivery_type',
                            'pickup_date_scheduled',
                            'delivery_date_scheduled',
                            'percentage_complete'
                        ];

}
