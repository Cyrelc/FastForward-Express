<?php

use Illuminate\Database\Seeder;

class ChargebacksTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('chargebacks')->insert([
            'name' => 'Cargo Bond',
            'default_amount' => 25.00,
            'gl_code' => "CB-00",
            'frequency' => "weekly",
        ]);

        DB::table('chargebacks')->insert([
            'name' => 'Cash Deliveries',
            'default_amount' => 0,
            'gl_code' => "CD-00",
            'frequency' => "weekly",
        ]);

        DB::table('chargebacks')->insert([
            'name' => 'Communication Rental',
            'default_amount' => 10,
            'gl_code' => "CR-00",
            'frequency' => "monthly",
        ]);
    }
}
