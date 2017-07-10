<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Roles extends Model
{
    public $primaryKey="role_id";
    public $timestamps = false;

    public function users() {
        return $this->belongsToMany('App\User', 'users');
    }
}
