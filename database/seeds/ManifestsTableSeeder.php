<?php

use Illuminate\Database\Seeder;

class ManifestsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker\Factory::create();
        
        for($i = 0; $i < 20; $i++) {
            $mid = DB::table('manifests')->insertGetId([
                "start_date" => new Carbon\Carbon('first day of last month'),
                "end_date" => new Carbon\Carbon('last day of last month')
            ]);

            $iid = factory(App\Invoice::class)->create()->invoice_id;

            $invoiceTotal = 0;
            $driverId = rand(1, 12);
			for($j = 11; $j < rand(15, 80); $j++) {

                $scenario = rand(0, 10);
                //Driver picked up and delivered bill
                if ($scenario > 5)
                    $bill = [
                        "pickup_driver_id" => $driverId,
                        "delivery_driver_id" => $driverId,
                    ];
                else if ($scenario < 8) //Driver picked up bill
                    $bill = [
                        "pickup_driver_id" => $driverId,
                        "delivery_driver_id" => rand(1, 12),
                    ];
                else //Driver delivered bill
                    $bill = [
                        "pickup_driver_id" => rand(1, 12),
                        "delivery_driver_id" => $driverId,
                    ];
                $weight = rand(0,2);

                $manifestScenario = rand(0,2);

                switch($weight) {
                    case 0:
                        $bill["is_invoiced"] = true;
                        $bill["invoice_id"] = $iid;
                        
                        if ($manifestScenario == 0) {
                            $bill["pickup_manifest_id"] = $mid;
                            $bill["is_pickup_manifested"] = true;
                        } else if ($manifestScenario == 1) {
                            $bill["delivery_manifest_id"] = $mid;
                            $bill["is_delivery_manifested"] = true;
                        } else {
                            $bill["pickup_manifest_id"] = $mid;
                            $bill["is_pickup_manifested"] = true;
                            $bill["delivery_manifest_id"] = $mid;
                            $bill["is_delivery_manifested"] = true;
                        }
                        break;
                    case 1:
                        $bill["is_invoiced"] = true;
                        $bill["invoice_id"] = $iid;
                        break;
                    case 2:                        
                        if ($manifestScenario == 0) {
                            $bill["pickup_manifest_id"] = $mid;
                            $bill["is_pickup_manifested"] = true;
                        } else if ($manifestScenario == 1) {
                            $bill["delivery_manifest_id"] = $mid;
                            $bill["is_delivery_manifested"] = true;
                        } else {
                            $bill["pickup_manifest_id"] = $mid;
                            $bill["is_pickup_manifested"] = true;
                            $bill["delivery_manifest_id"] = $mid;
                            $bill["is_delivery_manifested"] = true;
                        }
                        
                        if ($manifestScenario == 0) {
                            $bill["pickup_manifest_id"] = $mid;
                            $bill["is_pickup_manifested"] = true;
                        } else if ($manifestScenario == 1) {
                            $bill["delivery_manifest_id"] = $mid;
                            $bill["is_delivery_manifested"] = true;
                        } else {
                            $bill["pickup_manifest_id"] = $mid;
                            $bill["is_pickup_manifested"] = true;
                            $bill["delivery_manifest_id"] = $mid;
                            $bill["is_delivery_manifested"] = true;
                        }
                        break;
                }

                $bill["pickup_address_id"] = factory(App\Address::class)->create()->address_id;
                $bill["delivery_address_id"] = factory(App\Address::class)->create()->address_id;
                
				$invoiceTotal += factory(App\Bill::class)->create($bill)->amount * 1.05;
			}

            if ($i % 2 == 0) {
                for($k = 0; $k < rand(0, 3); $k++) {
                    $outcome = rand(1,6);

                    if ($outcome <= 4) {
                        //Keep creating payments until there's nothing left
                        while ($invoiceTotal > 0) {

                            $amount = 0;
                            while($amount == 0)
                                $amount = rand(1, $invoiceTotal);

                            if ($amount > $invoiceTotal)
                                $amount = $invoiceTotal;

                            $pid = factory(App\Payment::class)->create([
                                "invoice_id" => $iid,
                                "amount" => $amount,
                            ])->payment_id;

                            $invoiceTotal -= $amount;

                            if ($invoiceTotal < 0)
                                $invoiceTotal = 0;
                        }
                    } else if ($outcome < 6) {
                        //Create one large payment
                        $pid = factory(App\Payment::class)->create([
                            "invoice_id" => $iid,
                            "amount" => $invoiceTotal,
                        ])->payment_id;

                    } else {
                        //"Accidentally" overpay
                        $pid = factory(App\Payment::class)->create([
                            "invoice_id" => $iid,
                            "amount" => $invoiceTotal + 30,
                        ])->payment_id;
                    }
                }
            }
        }
    }
}
