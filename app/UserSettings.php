<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\ActivityLog\Traits\LogsActivity;

class UserSettings extends Model {
    protected $fillable = ['use_imperial_default', 'user_id'];
    
    protected static $logFillable = true;
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;
}
