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
        for($i = 0; $i < 10; $i++) {
            factory(App\Interliner::class)->create();
        }
    }
}
