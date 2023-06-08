<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class InvoiceSortOption extends Model {
    use LogsActivity;

    public $primaryKey = 'invoice_sort_option_id';
    public $timestamps = false;

    protected $fillable = [
        'database_field_name',
        'friendly_name',
        'can_be_subtotaled'
    ];

    public function getActivityLogOptions() : LogOptions {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
