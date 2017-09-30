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
            "grace_days" => 5,
            "mandatory" => true,
            "should_notify" => true,
            "notification_type" => "text",
            "selection_id" => 14
        ]);

        DB::table("expiries")->insert([
            "description" => "Insurance",
            "grace_days" => 0,
            "mandatory" => true,
            "should_notify" => true,
            "notification_type" => "email",
            "selection_id" => 13
        ]);
    }
}
