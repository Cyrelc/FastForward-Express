<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class Amendment extends Model {
    use LogsActivity;

    public $primaryKey = 'amendment_id';
    public $timestamps = false;

    protected $fillable = ['amount', 'bill_id', 'description', 'invoice_id'];

    protected static $logFillable = true;
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;
}
