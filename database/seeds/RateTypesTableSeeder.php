<?php

use Illuminate\Database\Seeder;

class RateTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('rate_types')->insert([
            'name' => 'Single Call',
            'description' => 'It\'s a single call.'
        ]);

        DB::table('rate_types')->insert([
            'name' => 'Corporate Client',
            'description' => 'It\'s a corporate client.'
        ]);
    }
}
