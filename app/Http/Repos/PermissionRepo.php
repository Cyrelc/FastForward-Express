<?php
namespace App\Http\Repos;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\User;

class PermissionRepo {

    public function assignUserPermissions($user, $permissions) {
        foreach($permissions as $name => $value)
            if($user->can($name) && !$value)
                $user->revokePermissionTo($name);
            elseif($user->cannot($name) && $value)
                $user->givePermissionTo($name);
    }

    public function GetByUserId($userId) {
        $user = User::where('user_id', $userId)->first();

        return $user->getAllPermissions();
    }
}
