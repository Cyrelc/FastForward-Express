<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    public $primaryKey = "payment_method_id";
    public $timestamps = false;
}
