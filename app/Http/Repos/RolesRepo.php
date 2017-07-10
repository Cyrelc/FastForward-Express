<?php
namespace App\Http\Repos;

use App\Roles;

class RolesRepo
{
    public function GetById($id) {
        $role = Roles::where('role_id', '=', $id)->first();

        return $role;
    }

    public function GetByName($name) {
        $role = Roles::where('name', '=', $name)->first();

        return $role;
    }
}
