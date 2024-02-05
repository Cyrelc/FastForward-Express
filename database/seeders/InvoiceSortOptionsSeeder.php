<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use DB;

class InvoiceSortOptionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('invoice_sort_options')->insert([
            'database_field_name' => 'time_pickup_scheduled',
            'friendly_name' => 'Bill Date',
            'can_be_subtotaled' => true
        ]);
    
        DB::table('invoice_sort_options')->insert([
            'database_field_name' => 'charge_account_id',
            'friendly_name' => 'Child Location',
            'can_be_subtotaled' => true,
            'contingent_field' => 'can_be_parent'
        ]);

        DB::table('invoice_sort_options')->insert([
            'database_field_name' => 'bill_number',
            'friendly_name' => 'Bill Number',
            'can_be_subtotaled' => false
        ]);

        DB::table('invoice_sort_options')->insert([
            'database_field_name' => 'charge_reference_value',
            'friendly_name' => 'Custom Field',
            'can_be_subtotaled' => true,
            'contingent_field' => 'uses_custom_field'
        ]);
    }
}
