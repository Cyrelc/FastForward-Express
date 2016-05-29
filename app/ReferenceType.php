<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ReferenceType extends Model {
    protected $table = 'ref_types';

    protected $fillable = [
        'name'
    ];
}
