<?php

use App\Customer;
use App\Driver;
use App\RateType;
use App\InvoiceInterval;

use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder {
    public function run() {
        $faker = Faker\Factory::create('en_CA');

        $numRates = RateType::count() - 1;
        $numInvoice = InvoiceInterval::count() - 1;
        $numDriver = Driver::count() - 1;

        for($i = 0; $i < 10; ++$i) {
            $addr = $faker->address;
            $post = $faker->postcode;
            $same = $faker->boolean(50);
            Customer::create(array(
                    'company_name' => $faker->company,
                    'address' => $addr,
                    'postal_code' => $post,
                    'bill_address' => $same ? $addr : $faker->address,
                    'bill_postal_code' => $same ? $post : $faker->postcode,
                    'contact_name' => $faker->name,
                    'phone_nums' => serialize([
                            ($faker->boolean(50) ? $faker->phoneNumber : null),
                            ($faker->boolean(50) ? $faker->phoneNumber : null),
                            ($faker->boolean(50) ? $faker->phoneNumber : null),
                            ($faker->boolean(50) ? $faker->phoneNumber : null)
                    ]),
                    'email' => 'FFE-COMPANY-' . $i . '@mailinator.com',
                    'parent_id' => ($faker->boolean(50) && ($i > 1)) ?
                            $faker->numberBetween(1, $i - 1) : null,
                    'rate_type_id' => $faker->numberBetween(1, $numRates),
                    'invoice_interval_id' => $faker->numberBetween(1, $numInvoice),
                    'invoice_start' => $faker->dateTimeThisYear(),
                    'autonumber_bills' => $faker->boolean(75),
                    'has_reference_field' => $faker->boolean(50),
                    'tax_exempt' => $faker->boolean(10),
                    'apply_interest' => $faker->boolean(10),
                    'driver_comm_id' => $faker->boolean(10) ?
                            $faker->numberBetween(1, $numDriver) : null
            ));
        }
    }
}
