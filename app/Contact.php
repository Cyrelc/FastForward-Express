<?php
namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class Contact extends Model
{
    use LogsActivity;

    public $primaryKey = "contact_id";
    public $timestamps = false;

    protected $fillable = ['first_name', 'last_name', 'position', 'enabled'];

    protected static $logFillable = true;
    protected static $logOnlyDirty = true;

    public function accounts() {
        return $this->belongsToMany('App\Account', 'account_contacts');
    }

    public function employees() {
        return $this->belongsToMany('App\Employee', 'employee_emergency_contacts');
    }
}
