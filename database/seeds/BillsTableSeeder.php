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
            //From and to account
            if ($scenario == 0) {
                $bill = [
                    "from_account_id" => rand(1, 40),
                    "to_account_id" => rand(1, 40),
                    "pickup_driver_id" => rand(1, 12),
                    "delivery_driver_id" => rand(1, 12),
                    "is_manifested" => false,
                    "is_invoiced" => false,
                    "bill_number" => $i + 1
                ];

                //Charge to random account
                if ($chargeScenario < 5)
                    $bill["charge_account_id"] = rand(1, 40);
                else if ($chargeScenario < 8) // Charge to "from" account
                    $bill["charge_account_id"] = $bill["from_account_id"];
                else //Charge to "to" account
                    $bill["charge_account_id"] = $bill["to_account_id"];
            }
            //From account, to address
            else if ($scenario == 1) {
                $bill = [
                    "from_account_id" => rand(1, 40),
                    "to_address_id" => factory(App\Address::class)->create()->address_id,
                    "pickup_driver_id" => rand(1, 12),
                    "delivery_driver_id" => rand(1, 12),
                    "is_manifested" => false,
                    "is_invoiced" => false,
                    "bill_number" => $i + 1
                ];

                //Charge to random account
                if ($chargeScenario < 3)
                    $bill["charge_account_id"] = rand(1, 40);
                else // Charge to "from" account
                    $bill["charge_account_id"] = $bill["from_account_id"];
            }
            //From address, to account
            else if ($scenario == 2) {
                $bill = [
                    "from_address_id" => factory(App\Address::class)->create()->address_id,
                    "to_account_id" => rand(1, 40),
                    "pickup_driver_id" => rand(1, 12),
                    "delivery_driver_id" => rand(1, 12),
                    "is_manifested" => false,
                    "is_invoiced" => false,
                    "bill_number" => $i + 1
                ];

                //Charge to random account
                if ($chargeScenario < 3)
                    $bill["charge_account_id"] = rand(1, 40);
                else // Charge to "from" account
                    $bill["charge_account_id"] = $bill["to_account_id"];
            }
            //From address, to address
            else {
                $bill = [
                    "from_address_id" => factory(App\Address::class)->create()->address_id,
                    "to_address_id" => factory(App\Address::class)->create()->address_id,
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
