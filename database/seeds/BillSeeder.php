<?php

use App\Bill;
use App\Customer;
use App\Driver;
use App\PaymentType;
use App\ReferenceType;

use Illuminate\Database\Seeder;

class BillSeeder extends Seeder {
    public function run() {
        $faker = Faker\Factory::create('en_CA');

        $numRef = ReferenceType::count() - 1;
        $numPay = PaymentType::count() - 1;
        $numCust = Customer::count() - 1;
        $numDriv = Driver::count() - 1;

        for($i = 0; $i < 20; ++$i) {
            $cost = $faker->numberBetween(10, 500);
            $int = $faker->boolean(20) ?
                        $faker->numberBetween(0, $cost - 1) : 0;

            $pickup = Driver::findOrFail($faker->numberBetween(1, $numDriv));
            $dropoff = $faker->boolean(70) ? $pickup :
                    Driver::findOrFail($faker->numberBetween(1, $numDriv));

            $customer = Customer::findOrFail($faker->numberBetween(1, $numCust));

            Bill::create(array(
                'number' => $faker->unique()->randomNumber(),
                'date' => $faker->dateTimeThisDecade(),
                'description' => $faker->boolean(50) ? $faker->sentence : null,
                'ref_id' => $faker->numberBetween(1, $numRef),
                'payment_id' => $faker->numberBetween(1, $numPay),
                'amount' => $cost,
                'int_amount' => $int,
                'driver_amount' => $cost - $int,
                'taxes' => $cost * env('TAX_AMOUNT', 0.05),
                'customer_id' => $customer->id,
                'driver_pickup_id' => $pickup->id,
                'pickup_amount' => $pickup->per_pickup * $cost,
                'driver_dropoff_id' => $dropoff->id,
                'dropoff_amount' => $dropoff->per_dropoff * $cost,
                'driver_comm' => $customer->hasDriverCommission() ?
                        $customer->getDriverComm->per_comm * $cost: 0
            ));
        }
    }
}
