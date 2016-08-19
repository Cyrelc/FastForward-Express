<?php

use Illuminate\Database\Seeder;

class ReferenceTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('reference_types')->insert([
            'name' => 'PO'
        ]);

        DB::table('reference_types')->insert([
            'name' => 'Cost Center'
        ]);

        DB::table('reference_types')->insert([
            'name' => 'Reference'
        ]);

        DB::table('reference_types')->insert([
            'name' => 'Sub Bills'
        ]);
    }
}
