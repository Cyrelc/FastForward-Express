<?php

use App\ReferenceType;

use Illuminate\Database\Seeder;

class ReferenceTypeSeeder extends Seeder {
    public function run() {
        ReferenceType::create(array('name' => 'PO'));
        ReferenceType::create(array('name' => 'Cost Centre'));
        ReferenceType::create(array('name' => 'Reference'));
        ReferenceType::create(array('name' => 'Sub Bills'));
    }
}
