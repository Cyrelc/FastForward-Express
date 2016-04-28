<?php

use App\Role;
use App\Permission;

use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder {
    public function run() {
        // Admin
        $role = Role::create(array('name' => 'admin'));
        // Gets all the roles.
        $role->permissions()->attach(Permission::all());

        // Placeholders
        $role = Role::create(array('name' => 'role1'));
        $role->permissions()->attach([1, 2, 3, 4]);
        $role = Role::create(array('name' => 'role2'));
        $role->permissions()->attach([5, 6, 7, 8, 9]);
    }
}
