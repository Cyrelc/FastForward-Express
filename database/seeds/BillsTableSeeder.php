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

            //From and to account
            if ($scenario == 0)
                factory(App\Bill::class)->create([
                    "from_account_id" => rand(1, 40),
                    "to_account_id" => rand(1, 40),
                    "pickup_driver_id" => rand(1, 12),
                    "delivery_driver_id" => rand(1, 12),
                    "is_manifested" => false,
                    "is_invoiced" => false,
                    "bill_number" => $i + 1
                ]);
            //From account, to address
            else if ($scenario == 1)
                factory(App\Bill::class)->create([
                    "from_account_id" => rand(1, 40),
                    "to_address_id" => factory(App\Address::class)->create()->address_id,
                    "pickup_driver_id" => rand(1, 12),
                    "delivery_driver_id" => rand(1, 12),
                    "is_manifested" => false,
                    "is_invoiced" => false,
                    "bill_number" => $i + 1
                ]);
            //From address, to account
            else if ($scenario == 2)
                factory(App\Bill::class)->create([
                    "from_address_id" => factory(App\Address::class)->create()->address_id,
                    "to_account_id" => rand(1, 40),
                    "pickup_driver_id" => rand(1, 12),
                    "delivery_driver_id" => rand(1, 12),
                    "is_manifested" => false,
                    "is_invoiced" => false,
                    "bill_number" => $i + 1
                ]);
            //From address, to address
            else
                factory(App\Bill::class)->create([
                    "from_address_id" => factory(App\Address::class)->create()->address_id,
                    "to_address_id" => factory(App\Address::class)->create()->address_id,
                    "pickup_driver_id" => rand(1, 12),
                    "delivery_driver_id" => rand(1, 12),
                    "is_manifested" => false,
                    "is_invoiced" => false,
                    "bill_number" => $i + 1
                ]);

        }
    }
}
