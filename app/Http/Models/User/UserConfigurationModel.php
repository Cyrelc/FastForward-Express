<?php
namespace App\Http\Models\User;

class UserConfigurationModel {
    public $authenticatedEmployee;
    public $authenticatedUser;
    public $contact;
    public $frontEndPermissions;
    public $is_impersonating = false;
    public $user_settings = [];
}

?>
