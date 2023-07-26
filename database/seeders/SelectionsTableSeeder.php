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
// Rate types
        DB::table('selections')->insert([
            'name' => 'Common Rate',
            'value' => 'commonRate',
            'type' => 'charge_type'
        ]);
        DB::table('selections')->insert([
            'name' => 'Distance Rate',
            'value' => 'distanceRate',
            'type' => 'charge_type'
        ]);
        DB::table('selections')->insert([
            'name' => 'Legacy Rate',
            'value' => 'legacyRate',
            'type' => 'charge_type'
        ]);
        DB::table('selections')->insert([
            'name' => 'Misc Rate',
            'value' => 'miscellaneousRate',
            'type' => 'charge_type'
        ]);
        DB::table('selections')->insert([
            'name' => 'Time Rate',
            'value' => 'timeRate',
            'type' => 'charge_type'
        ]);
        DB::table('selections')->insert([
            'name' => 'Weight Rate',
            'value' => 'weightRate',
            'type' => 'charge_type'
        ]);
// Vehicle Types
        DB::table('selections')->insert([
            'name' => '1/4 Ton Truck or Van',
            'value' => 'quarter_ton_truck_or_van',
            'type' => 'vehicle_type'
        ]);
        DB::table('selections')->insert([
            'name' => '1/2 or 3/4 Ton Truck or Van',
            'value' => 'half_or_three_quarter_ton_truck_or_van',
            'type' => 'vehicle_type'
        ]);
        DB::table('selections')->insert([
            'name' => 'Truck and 20 foot Trailer',
            'value' => 'truck_and_20_foot_trailer',
            'type' => 'vehicle_type'
        ]);
    }
}
