<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles;
    use LogsActivity;
    use Notifiable;

    public $primaryKey="user_id";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'email', 'username', 'is_enabled'];
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

    public function employee() {
        return $this->hasOne('App\Employee', 'user_id');
    }

    protected static $logFillable = true;
    protected static $logGuarded = false;
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;
}
