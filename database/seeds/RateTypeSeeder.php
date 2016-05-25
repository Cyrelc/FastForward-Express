<?php

use App\RateType;

use Illuminate\Database\Seeder;

class RateTypeSeeder extends Seeder {
    public function run() {
        RateType::create(array(
                'name' => 'Single Call'
        ));
        RateType::create(array(
                'name' => 'Corporate Client'
        ));
    }
}
