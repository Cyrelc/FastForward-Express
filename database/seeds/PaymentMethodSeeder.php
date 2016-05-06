<?php

use App\PaymentType;

use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder {
    public function run() {
        PaymentType::create(array('name' => 'Account'));
        PaymentType::create(array('name' => 'Cheque'));
        PaymentType::create(array('name' => 'Cash'));

        PaymentType::create(array('name' => 'Visa'));
        PaymentType::create(array('name' => 'MasterCard'));
        PaymentType::create(array('name' => 'American Express'));
    }
}
