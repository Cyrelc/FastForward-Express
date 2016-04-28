<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Role extends Model {
    protected $table = 'roles';

    protected $fillable = [
        'name'
    ];

    public function users() {
        return $this->hasMany('User', 'role_id', 'id');
    }

    public function permissions() {
        return $this->belongsToMany('App\Permission');
    }
}
