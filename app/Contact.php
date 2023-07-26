<?php
namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Contact extends Model
{
    use LogsActivity;

    public $primaryKey = "contact_id";
    public $timestamps = false;

    protected $fillable = ['first_name', 'last_name', 'position', 'enabled', 'preferred_name', 'pronouns'];

    public function getActivityLogOptions() : LogOptions {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function accounts() {
        return $this->belongsToMany(Account::class, 'account_users');
    }

    public function employees() {
        return $this->belongsToMany(Employee::class, 'employee_emergency_contacts');
    }

    public function email_addresses() {
        return $this->hasMany(EmailAddress::class);
    }

    public function primary_email() {
        return $this->hasOne(EmailAddress::class, 'contact_id')->where('is_primary', true);
    }
}
