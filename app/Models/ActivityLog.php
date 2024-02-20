<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    public $primaryKey = "id";
    public $timestamps = false;

    protected $table = 'activity_log';
    protected $fillable = ['log_name', 'description', 'subject_id', 'subject_type', 'causer_id', 'causer_type', 'properties', 'updated_at'];
}

?>

