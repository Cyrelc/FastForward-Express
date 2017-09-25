<?php

use Illuminate\Database\Seeder;

class SelectionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('selections')->insert([
            'name' => 'Weekly',
            'value' => 'weekly',
            'type' => 'invoice_interval'
        ]);

        DB::table('selections')->insert([
            'name' => 'Bi-Weekly',
            'value' => 'bi-weekly',
            'type' => 'invoice_interval'
        ]);

        DB::table('selections')->insert([
            'name' => 'Monthly',
            'value' => 'monthly',
            'type' => 'invoice_interval'
        ]);

        DB::table('selections')->insert([
            'name' => 'Cash',
            'value' => 'cash',
            'type' => 'prepaid_option'
        ]);

        DB::table('selections')->insert([
            'name' => 'Visa',
            'value' => 'visa',
            'type' => 'prepaid_option'
        ]);

        DB::table('selections')->insert([
            'name' => 'Mastercard',
            'value' => 'mastercard',
            'type' => 'prepaid_option'
        ]);

        DB::table('selections')->insert([
            'name' => 'Cheque',
            'value' => 'cheque',
            'type' => 'prepaid_option'
        ]);

        DB::table('selections')->insert([
            'name' => 'Regular',
            'value' => 'regular',
            'type' => 'delivery_type'
        ]);

        DB::table('selections')->insert([
            'name' => 'Rush',
            'value' => 'rush',
            'type' => 'delivery_type'
        ]);

        DB::table('selections')->insert([
            'name' => 'Direct',
            'value' => 'direct',
            'type' => 'delivery_type'
        ]);

        DB::table('selections')->insert([
            'name' => 'Direct Rush',
            'value' => 'direct_rush',
            'type' => 'delivery_type'
        ]);

        DB::table('selections')->insert([
            'name' => 'Hot Shot',
            'value' => 'hot_shot',
            'type' => 'delivery_type'
        ]);

        //TODO: Brandon -> insert your employee types here
        DB::table('selections')->insert([
            'name' => 'Full-Time Driver',
            'value' => 'ft_driver',
            'type' => 'employee_type'
        ]);

        DB::table('selections')->insert([
            'name' => 'Contractor Driver',
            'value' => 'c_driver',
            'type' => 'employee_type'
        ]);

        DB::table('selections')->insert([
            'name' => 'Office Employee',
            'value' => 'office',
            'type' => 'employee_type'
        ]);

        DB::table('selections')->insert([
            'name' => 'Employee Type 1',
            'value' => 'employee_type_1',
            'type' => 'employee_type'
        ]);
    }
}
