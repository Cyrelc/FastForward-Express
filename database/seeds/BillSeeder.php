<?php

use App\Bill;
use App\ReferenceType;
use App\PaymentType;

use Illuminate\Database\Seeder;

class BillSeeder extends Seeder {
    public function run() {
        Bill::create(array(
                'number' => 1,
                'date' => date('Y-m-d H:i:s'),
                'description' => 'this describes that',
                'ref_id' => rand(1, ReferenceType::count()),
                'payment_id' => rand(1, PaymentType::count()),
                'amount' => rand(1, 100) / rand(1, 5),
                'int_amount' => rand(1, 100) / rand(1, 5),
                'driver_amount' => rand(1, 100) / rand(1, 5),
                'taxes' => rand(1, 100) / rand(1, 5)
        ));
        Bill::create(array(
                'number' => 2,
                'date' => date('Y-m-d H:i:s'),
                'manifest' => date('Y-m-d H:i:s'),
                'ref_id' => rand(1, ReferenceType::count()),
                'payment_id' => rand(1, PaymentType::count()),
                'amount' => rand(1, 100) / rand(1, 5),
                'int_amount' => rand(1, 100) / rand(1, 5),
                'driver_amount' => rand(1, 100) / rand(1, 5),
                'taxes' => rand(1, 100) / rand(1, 5)
        ));
        Bill::create(array(
                'number' => 3,
                'date' => date('Y-m-d H:i:s'),
                'payment_id' => rand(1, PaymentType::count()),
                'amount' => rand(1, 100) / rand(1, 5),
                'int_amount' => rand(1, 100) / rand(1, 5),
                'driver_amount' => rand(1, 100) / rand(1, 5),
                'taxes' => rand(1, 100) / rand(1, 5)
        ));
    }
}
