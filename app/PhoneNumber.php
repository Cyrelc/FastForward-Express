<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class PhoneNumber extends Model
{
    use LogsActivity;

    public $primaryKey = "phone_number_id";
    public $timestamps = false;

    protected $fillable = ['phone_number', 'extension_number', 'is_primary', 'type', 'contact_id'];

    protected static $logFillable = true;
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;
}
