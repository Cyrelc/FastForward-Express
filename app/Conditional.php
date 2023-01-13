<?php

namespace App;


use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class Conditional extends Model {
    use LogsActivity;

    public $primaryKey = 'conditional_id';
    public $timestamps = true;

    protected $fillable = [
        'action',
        'human_readable',
        'json_logic',
        'name',
        'ratesheet_id',
        'value',
        'value_type'
    ];
}

?>
