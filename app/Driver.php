<?php

namespace App;

use App\Role;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Driver extends Authenticatable {
    protected $fillable = [
        'number', 'name', 'sin', 'pager', 'active', 'licence',
        'address', 'postal', 'phone', 'email', 'start',
        'per_pickup', 'per_dropoff', 'per_comm'
    ];

    protected $hidden = [
        'sin'
    ];
}
