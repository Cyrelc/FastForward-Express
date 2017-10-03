<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    public $timestamps = false;

    protected $fillable = [
    	'bill_id',
    	'weight',
    	'height',
    	'width',
    	'length'
    ];
}
