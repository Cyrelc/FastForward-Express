<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    public $primaryKey = "payment_id";
    public $timestamps = false;

    protected $fillable = [	'account_id',
                            'invoice_id',
                            'date',
                            'amount',
                            'payment_type',
                            'reference_value',
                            'comment'
                        ];

}
