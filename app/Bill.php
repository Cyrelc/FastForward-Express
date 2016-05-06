<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Bill extends Model {
    protected $table = 'bills';

    protected $fillable = [
        'number', 'date', 'description', 'ref_id', 'manifest',
        'payment_id', 'amount', 'int_amount', 'driver_amount',
        'taxes'
    ];
}
