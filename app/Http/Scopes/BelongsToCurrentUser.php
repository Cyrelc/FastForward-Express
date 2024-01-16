<?php

namespace App\Http\Scopes;

use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class BelongsToCurrentUser implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        $builder->where('user_id', auth()->id());
    }
}
