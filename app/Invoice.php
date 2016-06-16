<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model {
    protected $table = 'invoices';

    protected $fillable = [
        'number', 'date', 'printed_on', 'comment',
        'creator_id', 'last_modified_by_id'
    ];

    public function bills() {
        return $this->hasMany('App\Bill');
    }

    public function total() {
        return $this->bills->reduce(function($carry, $value) {
            return $carry + $value->amount;
        });
    }

    public function tax() {
        return $this->bills->reduce(function($carry, $value) {
            return $carry + $value->taxes;
        });
    }
}
