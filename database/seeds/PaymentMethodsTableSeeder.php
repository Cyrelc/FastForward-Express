<?php

use Illuminate\Database\Seeder;

class PaymentMethodsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('payment_methods')->insert([
            "stripe_id" => "pm1"
        ]);

        DB::table('payment_methods')->insert([
            "stripe_id" => "pm2"
        ]);

        DB::table('payment_methods')->insert([
            "stripe_id" => "pm3"
        ]);
    }
}
