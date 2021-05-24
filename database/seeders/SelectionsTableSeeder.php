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
//Intervals
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
//Delivery Types
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
//Driver Warning Types
        DB::table('selections')->insert([
            'name' => 'No Warning',
            'value' => 'no_warning',
            'type' => 'severity'
        ]);

        DB::table('selections')->insert([
            'name' => 'Warn But Continue',
            'value' => 'warn_continue',
            'type' => 'severity'
        ]);
        
        DB::table('selections')->insert([
            'name' => 'Warn and Suspend',
            'value' => 'warn_suspend',
            'type' => 'severity'
        ]);

        DB::table('selections')->insert([
            'name' => 'Warn and Terminate',
            'value' => 'warn_terminate',
            'type' => 'severity'
        ]);

//Employee types
        // DB::table('selections')->insert([
        //     'name' => 'Full-Time Driver',
        //     'value' => 'ft_driver',
        //     'type' => 'employee_type'
        // ]);

        // DB::table('selections')->insert([
        //     'name' => 'Contractor Driver',
        //     'value' => 'c_driver',
        //     'type' => 'employee_type'
        // ]);

        // DB::table('selections')->insert([
        //     'name' => 'Office Employee',
        //     'value' => 'office',
        //     'type' => 'employee_type'
        // ]);

        // DB::table('selections')->insert([
        //     'name' => 'Employee Type 1',
        //     'value' => 'employee_type_1',
        //     'type' => 'employee_type'
        // ]);
//Phone types
        DB::table('selections')->insert([
            'name' => 'Cell',
            'value' => 'cell',
            'type' => 'phone_type'
        ]);
        DB::table('selections')->insert([
            'name' => 'Home',
            'value' => 'home',
            'type' => 'phone_type'
        ]);
        DB::table('selections')->insert([
            'name' => 'Work',
            'value' => 'work',
            'type' => 'phone_type'
        ]);
        DB::table('selections')->insert([
            'name' => 'Fax',
            'value' => 'fax',
            'type' => 'phone_type'
        ]);
        DB::table('selections')->insert([
            'name' => 'Pager',
            'value' => 'pager',
            'type' => 'phone_type'
        ]);
//Email or Contact Types
        DB::table('selections')->insert([
            'name' => 'Billing',
            'value' => 'billing',
            'type' => 'contact_type'
        ]);
        DB::table('selections')->insert([
            'name' => 'Marketing',
            'value' => 'marketing',
            'type' => 'contact_type'
        ]);
        DB::table('selections')->insert([
            'name' => 'Owner',
            'value' => 'owner',
            'type' => 'contact_type'
        ]);
        DB::table('selections')->insert([
            'name' => 'Support',
            'value' => 'support',
            'type' => 'contact_type'
        ]);
    }
}
