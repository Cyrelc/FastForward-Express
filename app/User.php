<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Activitylog\Traits\LogsActivity;

class User extends Authenticatable
{
    use LogsActivity;

    public $primaryKey="user_id";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    protected static $logFillable = true;
    
    public function roles() {
        return $this->belongsToMany('App\Role', 'user_roles');
    }
}
