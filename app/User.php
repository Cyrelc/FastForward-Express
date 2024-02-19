<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\CausesActivity;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;


class User extends Authenticatable {
    use CausesActivity, HasApiTokens, HasFactory, HasRoles, LogsActivity, Notifiable, SoftDeletes;

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
        return $this->hasMany(AccountUser::class, 'user_id');
    }

    public function displayName() {
        if($this->employee) {
            return $this->employee->contact->display_name();
        } else if ($this->accountUsers) {
            return $this->accountUsers[0]->contact->display_name();
        } else
            return $this->email;
    }

    public function employee() : HasOne {
        return $this->hasOne(Employee::class, 'user_id');
    }

    public function getActivitylogOptions() : LogOptions {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
