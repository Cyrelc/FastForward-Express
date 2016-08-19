<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    public $primaryKey = "invoice_id";
    public $timestamps = false;
}
