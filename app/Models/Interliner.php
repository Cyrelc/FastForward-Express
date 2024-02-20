<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Interliner extends Model
{
    public $primaryKey = "interliner_id";
    public $timestamps = false;

    protected $fillable = [
    	'name',
    	'address_id'
    ];
}
