<?php

use Illuminate\Database\Seeder;

class SeveritiesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('severities')->insert([
            'name' => '0',
            'description' => 'No Warning',
        ]);

        DB::table('severities')->insert([
            'name' => '1',
            'description' => 'Warn But Continue',
        ]);

        DB::table('severities')->insert([
            'name' => '2',
            'description' => 'Warn And Suspend',
        ]);

        DB::table('severities')->insert([
            'name' => '3',
            'description' => 'Warn and Terminate',
        ]);
    }
}
