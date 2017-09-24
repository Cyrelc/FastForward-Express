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
                            'interliner_amount',
                            'skip_invoicing',
    						'bill_number',
    						'amount',
    						'date',
                            'description',
                            'num_pieces',
                            'weight',
                            'height',
                            'length',
                            'width',
                            'delivery_type',
                            'call_received',
                            'picked_up',
                            'delivered'
                        ];

}
