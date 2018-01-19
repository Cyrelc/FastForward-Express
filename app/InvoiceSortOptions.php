<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InvoiceSortOptions extends Model
{
    public $primaryKey = "invoice_sort_option_id";
    public $timestamps = false;

    protected $fillable = [	'database_field_name',
                        'friendly_name',
                        'can_be_subtotaled'
                        ];

}
