<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    public $timestamps = false;
    public $primaryKey = "package_id";

    protected $fillable = [
    	'bill_id',
    	'weight',
    	'height',
    	'width',
    	'length'
    ];
}
