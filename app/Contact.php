<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    public $primaryKey = "contact_id";
    public $timestamps = false;

    protected $fillable = ['first_name', 'last_name', 'enabled'];

    public function accounts() {
        return $this->belongsToMany('App\Account', 'account_contacts');
    }

    public function drivers() {
        return $this->belongsToMany('App\Driver', 'driver_emergency_contacts');
    }
}
