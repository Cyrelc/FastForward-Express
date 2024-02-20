<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class EmailAddress extends Model
{
    use HasFactory, LogsActivity;

    public $primaryKey = 'email_address_id';
    public $timestamps = false;

    protected $fillable = ['email', 'is_primary', 'contact_id', 'type'];

    protected $casts = ['type' => 'array'];

    public function typeSelections() {
        return Selection::where('type', 'contact_type')->select('name as label', 'selection_id as value')->get();
    }

    public function getActivityLogOptions() : LogOptions {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
