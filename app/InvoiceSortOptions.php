<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class InvoiceSortOptions extends Model
{
    use LogsActivity;

    public $primaryKey = "invoice_sort_option_id";
    public $timestamps = false;

    protected $fillable = [	'database_field_name',
                        'friendly_name',
                        'can_be_subtotaled'
                        ];

    protected static $logFillable = true;
    protected static $logOnlyDirty = true;
}
