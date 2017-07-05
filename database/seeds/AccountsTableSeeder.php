<?php

use Illuminate\Database\Seeder;

class AccountsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker\Factory::create();

        for($i = 0; $i <= 40; $i++) {
            $sad = factory(App\Address::class)->create();

            if (rand(0, 3) == 1) {
                $bad = null;
            } else {
                $bad = factory(App\Address::class)->create();
            }

            if ($i % 7 == 0)
                $hasSubAccounts = true;
            else
                $hasSubAccounts = false;

            $attrs = [
                "account_number" => $i,
                "stripe_id" => $i,
                "is_master" => true,
                "gets_discount" => $i % 5 == 0 ? true : false,
                "discount" => $i % 5 == 0 ? rand(1, 5) / 10 : 0,
                "shipping_address_id" => $sad->address_id,
                "can_be_parent" => $hasSubAccounts,
                "invoice_comment" => "",
                "fuel_surcharge" => rand(0,3) / 10
            ];

            if (rand(0, 1) == 1) {
                $attrs["invoice_comment"] = $faker->text(32);
            }

            if ($bad != null) {
                $attrs["billing_address_id"] = $bad->address_id;
            }

            $a = factory(App\Account::class)
                ->create($attrs);

            for ($j = 0; $j < rand(1, 7); $j++) {
                $primary = false;
                if ($j == 0) $primary = true;
                $cid = factory(App\Contact::class)->create()->contact_id;

                for($pns = 0; $pns < 2; $pns++) {
                    $isPrimary = false;
                    if ($pns == 0)
                        $isPrimary = true;

                    factory(App\PhoneNumber::class)->create(['contact_id'=>$cid, 'is_primary'=>$isPrimary]);
                }

                for($ems = 0; $ems < 2; $ems++) {
                    $isPrimary = false;
                    if ($ems == 0)
                        $isPrimary = true;

                    factory(App\EmailAddress::class)->create(['contact_id'=>$cid, 'is_primary'=>$isPrimary]);
                }

                for($adds = 0; $adds < 2; $adds++) {
                    $isPrimary = false;
                    if ($adds == 0)
                        $isPrimary = true;

                    factory(App\Address::class)->create(['contact_id'=>$cid, 'is_primary'=>$isPrimary]);
                }

                DB::table("account_contacts")->insert([
                    "contact_id" => $cid,
                    "account_id" => $a->account_id,
                    "is_primary" => $primary
                ]);
            }

            if ($hasSubAccounts) {
                for($k = 0; $k < rand(1, 5); $k++){
                    $sadr = factory(App\Address::class)->create();
                    if (rand(0, 3) == 1) {
                        $badr = $sadr;
                    } else {
                        $badr = factory(App\Address::class)->create();
                    }
                    $subAccount = [
                        "account_number" => $i . '-' . $k . '-sub',
                        "stripe_id" => $i . '-' . $k . '-sub',
                        "is_master" => false,
                        "can_be_parent" => false,
                        "parent_account_id" => $a->account_id,
                        "billing_address_id" => $badr->address_id,
                        "shipping_address_id" => $sadr->address_id
                    ];

                    $aid = factory(App\Account::class)
                        ->create($subAccount)->account_id;

                    for ($j = 0; $j < rand(2, 10); $j++) {
                        $primary = false;
                        if ($j == 0) $primary = true;
                        $cid = factory(App\Contact::class)->create()->contact_id;

                        for($pns = 0; $pns < 2; $pns++) {
                            $isPrimary = false;
                            if ($pns == 0)
                                $isPrimary = true;

                            factory(App\PhoneNumber::class)->create(['contact_id'=>$cid, 'is_primary'=>$isPrimary]);
                        }

                        for($ems = 0; $ems < 2; $ems++) {
                            $isPrimary = false;
                            if ($ems == 0)
                                $isPrimary = true;

                            factory(App\EmailAddress::class)->create(['contact_id'=>$cid, 'is_primary'=>$isPrimary]);
                        }

                        for($adds = 0; $adds < 2; $adds++) {
                            $isPrimary = false;
                            if ($adds == 0)
                                $isPrimary = true;

                            factory(App\Address::class)->create(['contact_id'=>$cid, 'is_primary'=>$isPrimary]);
                        }

                        DB::table("account_contacts")->insert([
                            "contact_id" => $cid,
                            "account_id" => $aid,
                            "is_primary" => $primary
                        ]);
                    }
                }
            }
        }
    }
}
