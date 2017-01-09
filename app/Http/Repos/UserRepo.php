<?php
namespace App\Http\Repos;

use App\User;
use Illuminate\Support\Facades\Hash;

class UserRepo
{
    public function Insert($user, $roleName) {
        $new = new User;

        $user = array_merge($user, array(
            'password' => Hash::make('ffe'),
            'is_locked' => false,
            'login_attempts' => 0,
            'remember_token' => null
        ));

        $new = $new->create($user);

        $roleRepo = new RolesRepo();
        $role = $roleRepo->GetByName($roleName);
        $roleRepo->AddUserToRole($new, $role);

        return $new;
    }
}
