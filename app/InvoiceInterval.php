<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InvoiceInterval extends Model {
    protected $table = 'invoice_interval';

    protected $fillable = [
        'name', 'num_days', 'num_months'
    ];

    public function getCompanies() {
        return $this->belongsToMany('App\Customer', 'invoice_interval_id');
    }
}
