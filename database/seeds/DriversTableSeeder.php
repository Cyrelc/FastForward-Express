<?php

use Illuminate\Database\Seeder;

class DriversTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker\Factory::create();
        $employeeId;

        for($i = 0; $i < 12; $i++) {
            $contactId = factory(App\Contact::class)->create()->contact_id;

            factory(App\PhoneNumber::class)->create([
                'contact_id'=>$contactId,
                'is_primary'=>1
            ]);

            factory(App\PhoneNumber::class)->create([
                'contact_id'=>$contactId,
                'is_primary'=>0
            ]);

            factory(App\EmailAddress::class)->create([
                'contact_id'=>$contactId,
                'is_primary'=>1
            ]);

            factory(App\EmailAddress::class)->create([
                'contact_id'=>$contactId,
                'is_primary'=>0
            ]);

            factory(App\Address::class)->create([
                'contact_id'=>$contactId,
                'is_primary'=>1
            ]);

            $employeeId = factory(App\Employee::class)->create([
                "contact_id" => $contactId,
                "user_id" => function(){
                    $uid = factory(App\User::class)->create()->user_id;

                    DB::table('user_roles')->insert([
                        "user_id" => $uid,
                        "role_id" => 2
                    ]);

                    return $uid;
                }
            ])->employee_id;

            $d = factory(App\Driver::class)->create([
                "employee_id" => $employeeId
            ]);

            //Add expiries
            $eid = DB::table('driver_expiries')->insertGetId([
                'driver_id' => $d->driver_id,
                'expiry_id' => 2
            ]);

            if (rand(0, 1) == 1) {
                $eid = DB::table('driver_expiries')->insertGetId([
                    'driver_id' => $d->driver_id,
                    'expiry_id' => 1
                ]);

            }

            for($j = 0; $j <= rand(2, 5); $j++) {
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

                DB::table('employee_emergency_contacts')->insert([
                    "contact_id" => $cid,
                    "employee_id" => $employeeId,
                    "is_primary" => $j === 0
                ]);
            }
        }
    }
}
