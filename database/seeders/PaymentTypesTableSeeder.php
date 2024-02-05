<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use DB;

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
            'type' => 'account'
        ]);
        DB::table('payment_types')->insert([
            'name' => 'Driver',
            'required_field' => null,
            'type' => 'driver'
        ]);
        DB::table('payment_types')->insert([
            'name' => 'Cash',
            'required_field' => null,
            'type' => 'prepaid'
        ]);
        DB::table('payment_types')->insert([
            'name' => 'Visa',
            'required_field' => 'Last Four Digits',
            'type' => 'prepaid'
        ]);
        DB::table('payment_types')->insert([
            'name' => 'Mastercard',
            'required_field' => 'Last Four Digits',
            'type' => 'prepaid'
        ]);
        DB::table('payment_types')->insert([
            'name' => 'Bank Transfer',
            'required_field' => 'Bank Transfer Id',
            'type' => 'prepaid'
        ]);
        DB::table('payment_types')->insert([
            'name' => 'Cheque',
            'required_field' => 'Cheque Number',
            'type' => 'prepaid'
        ]);
    }
}
