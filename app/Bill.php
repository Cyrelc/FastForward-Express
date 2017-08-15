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
    						'pickup_driver_id',
    						'delivery_driver_id',
    						'pickup_address_id',
    						'delivery_address_id',
    						'pickup_driver_percentage',
    						'delivery_driver_percentage',
    						'bill_number',
    						'amount',
    						'date',
    						'description'];

    public function Driver() {
        return $this->belongsTo('App\Driver');
    }
}
