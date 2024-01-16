<?php

namespace App;

use App\Http\Scopes\BelongsToCurrentUser;

use Illuminate\Database\Eloquent\Model;

class Query extends Model {
    protected $table = 'saved_queries';
    protected static function booted() {
        static::addGlobalScope(new BelongsToCurrentUser);
    }

    public $timestamps = true;

    protected $fillable = [
        'name',
        'query_string',
        'table',
        'user_id'
    ];
}
