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
        factory(App\Contact::class, 10)->create()->each(function($c){
            factory(App\PhoneNumber::class, 3)->create()->each(function($p) use (&$c){
                $isPrimary = $p->id % 3 == 0;
                DB::table('contact_phone_numbers')->insert([
                    'contact_id' => $c->id,
                    'phone_number_id' => $p->id,
                    'is_primary' => $isPrimary
                ]);
            });

            factory(App\EmailAddress::class)->create()->each(function($e) use (&$c){
                DB::table('contact_email_addresses')->insert([
                    'contact_id' => $c->id,
                    'email_address_id' => $e->email_address_id,
                    'is_primary' => true
                ]);
            });

            factory(App\Address::class)->create()->each(function($a) use (&$c){
                DB::table('contact_addresses')->insert([
                    'contact_id' => $c->id,
                    'address_id' => $a->address_id,
                    'is_primary' => true
                ]);
            });
        });
    }
}