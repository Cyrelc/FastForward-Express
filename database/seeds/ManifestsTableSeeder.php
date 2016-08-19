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
        for($i = 0; $i < rand(3, 8); $i++) {
			$driverId = rand(1, 4);
			$accountId = rand(1, 3);

            $mid = DB::table('manifests')->insertGetId([
                "driver_id" => $driverId,
                "start_date" => new Carbon\Carbon('first day of last month'),
				"end_date" => new Carbon\Carbon('last day of last month')
            ]);

            DB::table('manifest_modifications')->insert([
                "manifest_id" => $mid,
                "modification_id" => factory(App\Modification::class)->create([
                    "comment" => "Created manifest"
                ])->modification_id
            ]);

            $iid = factory(App\Invoice::class)->create([
                "invoice_number" => $i
            ])->invoice_id;

            DB::table('invoice_modifications')->insert([
                "invoice_id" => $iid,
                "modification_id" => factory(App\Modification::class)->create([
                    "comment" => "Created invoice"
                ])->modification_id
            ]);

            $invoiceTotal = 0;

			for($j = 11; $j < rand(15, 80); $j++) {

                $bill = [
					"account_id" => $accountId,
					"driver_id" => $driverId,
					"bill_number" => $i . "-" . $j,
                    "is_manifested" => false,
                    "is_invoiced" => true
				];

                $weight = rand(0,2);

                if ($weight == 0) {
                    $bill["is_invoiced"] = true;
                    $bill["invoice_id"] = $iid;
                    $bill["is_manifested"] = true;
					$bill["manifest_id"] = $mid;
                } else if ($weight == 1) {
                    $bill["is_invoiced"] = true;
                    $bill["invoice_id"] = $iid;

                } else if ($weight == 2){
                    $bill["is_manifested"] = true;
					$bill["manifest_id"] = $mid;
                }

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

                            DB::table('payment_modifications')->insert([
                                "payment_id" => $pid,
                                "modification_id" => factory(App\Modification::class)->create([
                                    "comment" => "Created payment"
                                ])->modification_id
                            ]);

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

                        DB::table('payment_modifications')->insert([
                            "payment_id" => $pid,
                            "modification_id" => factory(App\Modification::class)->create([
                                "comment" => "Created payment"
                            ])->modification_id
                        ]);
                    } else {
                        //"Accidentally" overpay
                        $pid = factory(App\Payment::class)->create([
                            "invoice_id" => $iid,
                            "amount" => $invoiceTotal + 30,
                        ])->payment_id;

                        DB::table('payment_modifications')->insert([
                            "payment_id" => $pid,
                            "modification_id" => factory(App\Modification::class)->create([
                                "comment" => "Created payment"
                            ])->modification_id
                        ]);
                    }
                }
            }
        }
    }
}
