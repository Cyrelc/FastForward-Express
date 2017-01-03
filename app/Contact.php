<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    public $primaryKey = "contact_id";
    public $timestamps = false;

    protected $fillable = ['first_name', 'last_name'];

    public function accounts() {
        return $this->belongsToMany('App\Account', 'account_contacts');
    }
}
