<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class EmployeeEmergencyContact extends Model
{
    use HasFactory, LogsActivity;

    public $primaryKey = 'contact_id';
    public $timestamps = false;

    protected $fillable = ['employee_id', 'contact_id', 'is_primary'];

    public function contact() {
        return $this->hasOne(Contact::class, 'contact_id');
    }

    public function employee() {
        return $this->hasOne(Employee::class, 'employee_id');
    }

    public function getActivityLogOptions() : LogOptions {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}

