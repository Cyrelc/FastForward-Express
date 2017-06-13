<?php

use Illuminate\Database\Seeder;

class CommissionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('commissions')->insert([
            "driver_id" => 1,
            "account_id" => 1,
            "commission" => 0.05
        ]);
    }
}
