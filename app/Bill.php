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

    public function paymentType() {
        return $this->hasOne('App\PaymentType', 'id', 'payment_id');
    }

    public function hasReference() {
        return !!($this->ref_id);
    }

    public function hasManifested() {
        return !!($this->manifest);
    }

    public function referenceType() {
        return $this->hasOne('App\ReferenceType', 'id', 'ref_id');
    }
}
