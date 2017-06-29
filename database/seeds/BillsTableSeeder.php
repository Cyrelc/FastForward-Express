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
        for($i = 0; $i < 10; $i++) {
            factory(App\Bill::class)->create([
                "account_id" => rand(1, 3),
                "pickup_driver_id" => rand(1, 4),
                "delivery_driver_id" => rand(1, 4),
                "is_manifested" => false,
                "is_invoiced" => false,
                "bill_number" => $i
            ]);
        }
    }
}
