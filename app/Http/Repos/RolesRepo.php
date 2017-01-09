<?php
namespace App\Http\Repos;

use App\Roles;
use DB;

class RolesRepo
{
    public function GetByName($name) {
        $role = Roles::where('name', '=', $name)->first();

        return $role;
    }

    public function AddUserToRole($user, $role) {
        DB::table('user_roles')->insert(array(
            'user_id' => $user->user_id,
            'role_id' => $role->role_id
        ));
    }
}
