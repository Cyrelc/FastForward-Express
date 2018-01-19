<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AccountInvoiceSortEntries extends Model
{
    public $primaryKey = "account_invoice_sort_entry_id";
    public $timestamps = false;

    protected $fillable = [	'account_id',
                        'invoice_sort_option_id',
                        'priority',
                        'subtotal'
                        ];

}
