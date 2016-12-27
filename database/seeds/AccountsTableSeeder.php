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
        for($i = 0; $i < rand(5, 40); $i++) {
            
            $sad = factory(App\Address::class)->create();

            if (rand(0, 3) == 1) {
                $bad = $sad;
            } else {
                $bad = factory(App\Address::class)->create();
            }
            

            $a = factory(App\Account::class)
                ->create([
                    // "user_id" => function(){
                    //     $uid = factory(App\User::class)->create()->user_id;

                    //     DB::table("user_roles")->insert([
                    //         "user_id" => $uid,
                    //         "role_id" => 3
                    //     ]);

                    //     return $uid;
                    // },
                    "account_number" => $i,
                    "stripe_id" => $i,
                    "is_master" => true,
                    "billing_address_id" => $bad->address_id,
                    "shipping_address_id" => $sad->address_id,
            ]);

            for ($j = 0; $j < rand(1, 3); $j++) {
                $primary = false;
                if ($j == 0) $primary = true;

                DB::table("account_contacts")->insert([
                    "contact_id" => factory(App\Contact::class)->create()->contact_id,
                    "account_id" => $a->account_id,
                    "is_primary" => $primary
                ]);
            }
            
            if ($i % 7 == 0) {
                for($k = 0; $k < rand(1, 5); $k++){
                    $sadr = factory(App\Address::class)->create();
                    if (rand(0, 3) == 1) {
                        $badr = $sadr;
                    } else {
                        $badr = $bad;
                    }

                    factory(App\Account::class)
                        ->create([
                            // "user_id" => function(){
                            //     $uid = factory(App\User::class)->create()->user_id;

                            //     DB::table("user_roles")->insert([
                            //         "user_id" => $uid,
                            //         "role_id" => 3
                            //     ]);

                            //     return $uid;
                            // },
                            "account_number" => $i . '-' . $k . '-sub',
                            "stripe_id" => $i . '-' . $k . '-sub',
                            "is_master" => false,
                            "billing_address_id" => $badr->address_id,
                            "shipping_address_id" => $sadr->address_id
                    ]);
                }
            }
        }
    }
}
