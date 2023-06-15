<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;


class User extends Authenticatable
{
    use HasApiTokens, HasRoles, LogsActivity, Notifiable;

    public $primaryKey="user_id";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['email', 'is_enabled', 'name', 'username'];
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
        return $this->hasMany('App\AccountUser', 'user_id');
    }

    public function displayName() {
        if($this->employee) {
            $contact = $this->employee->contact;
            return $contact->first_name . ' ' . $contact->last_name;
        }
    }

    public function employee() {
        return $this->hasOne('App\Employee', 'user_id');
    }

    public function getActivitylogOptions() : LogOptions {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
