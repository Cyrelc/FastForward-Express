<?php

use Illuminate\Database\Seeder;

class InterlinersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(App\Interliner::class, 10)->create();
    }
}
