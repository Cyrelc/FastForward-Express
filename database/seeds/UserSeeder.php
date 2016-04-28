<?php

use App\Role;
use App\User;

use Illuminate\Database\Seeder;

class UserSeeder extends Seeder {
    public function run() {
        User::create(array(
                'name' => 'admin',
                'email' => 'FFE-Admin@mailinator.com',
                'password' => 'pass',
                'role_id' => Role::where('name', '=', 'admin')->firstOrFail()->id
        ));
        User::create(array(
                'name' => 'user1',
                'email' => 'FFE-User1@mailinator.com',
                'password' => 'pass',
                'role_id' => 2
        ));
        User::create(array(
                'name' => 'user2',
                'email' => 'FFE-User2@mailinator.com',
                'password' => 'pass',
                'role_id' => 3
        ));
    }
}
