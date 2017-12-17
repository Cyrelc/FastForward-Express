<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Selection extends Model
{
    protected $fillable = [
    	'selection_id',
    	'name',
    	'value',
    	'type'
    ];
}
