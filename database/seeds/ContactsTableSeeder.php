<?php

use Illuminate\Database\Seeder;

class ContactsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(App\Address::class, 10)->create()->each(function($a){
            factory(App\Contact::class)->create([
                    'address_id' => $a->address_id
                ])->each(function($c){

                    for ($i = 0; $i <= 3; $i++){
                        factory(App\PhoneNumber::class)->create([
                            'contact_id' => $c->contact_id,
                            'is_primary' => $i == 0
                        ]);
                    }

                    for ($i = 0; $i <= 3; $i++){
                        factory(App\EmailAddress::class)->create([
                            'contact_id' => $c->contact_id,
                            'is_primary' => $i == 0
                        ]);
                    }
                }
            );
        });
    }
}