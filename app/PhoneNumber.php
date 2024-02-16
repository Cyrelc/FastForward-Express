<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class PhoneNumber extends Model
{
    use HasFactory, LogsActivity;

    public $primaryKey = "phone_number_id";
    public $timestamps = false;

    protected $fillable = ['phone_number', 'extension_number', 'is_primary', 'type', 'contact_id'];

    public function typeSelections() {
        return \App\Selection::where('type', 'phone_type')->select('name as label', 'selection_id as value')->get();
    }

    public function getActivityLogOptions() : LogOptions {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
