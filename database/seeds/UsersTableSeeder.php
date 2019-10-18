<?php

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $usernames = [
            "ffe-admin@mailinator.com",
            "ffe-admin2@mailinator.com",
            "henry@savior.swan"
        ];
        for($i=0; $i<3; $i++){
            DB::table('users')->insert([
                'username' => $usernames[$i],
                'email' => $usernames[$i],
                'password' => Hash::make("password"),
            ]);
            // DB::table('user_roles')->insert([
            //     'user_id' => factory(App\User::class)->create([
            //         "email" => $usernames[$i]
            //     ])->user_id,
            //     'role_id' => 1
            // ]);
        }
    }
}
