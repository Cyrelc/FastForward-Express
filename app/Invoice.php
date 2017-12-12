<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    public $primaryKey = "invoice_id";
    public $timestamps = false;

    public $fillable = [
        'account_id',
        'date',
        'bill_cost',
        'tax',
        'discount',
        'total_cost',
        'balance_owing'
    ];
}
