<?php

use Illuminate\Database\Seeder;

class PaymentTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('payment_types')->insert([
            'name' => 'Account',
            'required_field' => null,
            'default_ratesheet_id' => 1,
            'type' => 'account'
        ]);
        DB::table('payment_types')->insert([
            'name' => 'Driver',
            'required_field' => null,
            'default_ratesheet_id' => 1,
            'type' => 'driver'
        ]);
        DB::table('payment_types')->insert([
            'name' => 'Cash',
            'required_field' => null,
            'default_ratesheet_id' => 1,
            'type' => 'prepaid'
        ]);
        DB::table('payment_types')->insert([
            'name' => 'Visa',
            'required_field' => 'Last Four Digits',
            'default_ratesheet_id' => 1,
            'type' => 'prepaid'
        ]);
        DB::table('payment_types')->insert([
            'name' => 'Mastercard',
            'required_field' => 'Last Four Digits',
            'default_ratesheet_id' => 1,
            'type' => 'prepaid'
        ]);
        DB::table('payment_types')->insert([
            'name' => 'Bank Transfer',
            'required_field' => 'Bank Transfer Id',
            'default_ratesheet_id' => 1,
            'type' => 'prepaid'
        ]);
        DB::table('payment_types')->insert([
            'name' => 'Cheque',
            'required_field' => 'Cheque Number',
            'default_ratesheet_id' => 1,
            'type' => 'prepaid'
        ]);
    }
}
