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
        for($i=0; $i<2; $i++){
            DB::table('user_roles')->insert([
                'user_id' => factory(App\User::class)->create()->user_id,
                'role_id' => 1
            ]);
        }
    }
}
