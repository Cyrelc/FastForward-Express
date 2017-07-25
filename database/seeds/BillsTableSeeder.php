<?php

use Illuminate\Database\Seeder;

class BillsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        for($i = 0; $i < 100; $i++) {
            $scenario = rand(0,3);
            $chargeScenario = rand(0, 10);

            $bill = [];
            //Pickup and to account
            if ($scenario == 0) {
                $bill = [
                    "pickup_account_id" => rand(1, 40),
                    "delivery_account_id" => rand(1, 40),
                    "pickup_driver_id" => rand(1, 12),
                    "delivery_driver_id" => rand(1, 12),
                    "is_manifested" => false,
                    "is_invoiced" => false,
                    "bill_number" => $i + 1
                ];

                //Charge to random account
                if ($chargeScenario < 5)
                    $bill["charge_account_id"] = rand(1, 40);
                else if ($chargeScenario < 8) // Charge to "pickup" account
                    $bill["charge_account_id"] = $bill["pickup_account_id"];
                else //Charge to "to" account
                    $bill["charge_account_id"] = $bill["delivery_account_id"];
            }
            //Pickup account, to address
            else if ($scenario == 1) {
                $bill = [
                    "pickup_account_id" => rand(1, 40),
                    "delivery_address_id" => factory(App\Address::class)->create()->address_id,
                    "pickup_driver_id" => rand(1, 12),
                    "delivery_driver_id" => rand(1, 12),
                    "is_manifested" => false,
                    "is_invoiced" => false,
                    "bill_number" => $i + 1
                ];

                //Charge to random account
                if ($chargeScenario < 3)
                    $bill["charge_account_id"] = rand(1, 40);
                else // Charge to "pickup" account
                    $bill["charge_account_id"] = $bill["pickup_account_id"];
            }
            //Pickup address, to account
            else if ($scenario == 2) {
                $bill = [
                    "pickup_address_id" => factory(App\Address::class)->create()->address_id,
                    "delivery_account_id" => rand(1, 40),
                    "pickup_driver_id" => rand(1, 12),
                    "delivery_driver_id" => rand(1, 12),
                    "is_manifested" => false,
                    "is_invoiced" => false,
                    "bill_number" => $i + 1
                ];

                //Charge to random account
                if ($chargeScenario < 3)
                    $bill["charge_account_id"] = rand(1, 40);
                else // Charge to "pickup" account
                    $bill["charge_account_id"] = $bill["delivery_account_id"];
            }
            //Pickup address, to address
            else {
                $bill = [
                    "pickup_address_id" => factory(App\Address::class)->create()->address_id,
                    "delivery_address_id" => factory(App\Address::class)->create()->address_id,
                    "pickup_driver_id" => rand(1, 12),
                    "delivery_driver_id" => rand(1, 12),
                    "is_manifested" => false,
                    "is_invoiced" => false,
                    "bill_number" => $i + 1
                ];

                //Charge to random account
                $bill["charge_account_id"] = rand(1, 40);
            }

            factory(App\Bill::class)->create($bill);
        }
    }
}
