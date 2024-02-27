<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\CausesActivity;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;


class User extends Authenticatable {
    use CausesActivity, HasApiTokens, HasFactory, HasRoles, LogsActivity, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['email', 'is_enabled', 'name', 'username'];
    protected $guard_name = 'web';
    protected $guarded = ['password'];
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function accountUsers() {
        return $this->hasMany(AccountUser::class);
    }

    public function getContactAttribute() {
        if($this->employee && $this->employee->contact)
            return $this->employee->contact;

        if($this->accountUsers)
            return $this->accountUsers->first()->contact;

        return null;
    }

    public function displayName() {
        if($this->employee) {
            return $this->contact->display_name();
        } else if ($this->accountUsers) {
            return $this->contact->display_name();
        } else
            return $this->email;
    }

    public function employee() : hasOne {
        return $this->hasOne(Employee::class);
    }

    public function settings() : HasOne {
        return $this->hasOne(UserSettings::class);
    }

    public function getActivitylogOptions() : LogOptions {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
