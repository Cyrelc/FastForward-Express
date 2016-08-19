<?php

use Illuminate\Database\Seeder;

class ExpiriesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table("expiries")->insert([
            "description" => "Vehicle Damage",
            "grace" => 5,
            "mandatory" => true,
            "should_notify" => true,
            "notification_type" => "text",
            "severity_id" => 2
        ]);

        DB::table("expiries")->insert([
            "description" => "Insurance",
            "grace" => 0,
            "mandatory" => true,
            "should_notify" => true,
            "notification_type" => "email",
            "severity_id" => 1
        ]);
    }
}
