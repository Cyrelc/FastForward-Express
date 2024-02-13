<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Contact extends Model {
    use HasFactory, LogsActivity;

    public $primaryKey = 'contact_id';
    public $timestamps = false;

    protected $fillable = ['first_name', 'last_name', 'position', 'preferred_name', 'pronouns'];

    public function getActivityLogOptions() : LogOptions {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function accounts() {
        return $this->belongsToMany(\App\Account::class, 'account_users');
    }

    public function address() {
        return $this->hasOne(Address::class, 'contact_id');
    }

    public function employees() {
        return $this->belongsToMany(\App\Employee::class, 'employee_emergency_contacts');
    }

    public function email_addresses() {
        return $this->hasMany(EmailAddress::class, 'contact_id');
    }

    public function displayName() {
        return $this->preferred_name ?? $this->first_name . ' ' . $this->last_name;
    }

    public function phone_numbers() {
        return $this->hasMany(PhoneNumber::class, 'contact_id');
    }

    public function primary_email() {
        return $this->hasOne(EmailAddress::class, 'contact_id')->where('is_primary', true);
    }
}
