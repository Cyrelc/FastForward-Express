<?php

use App\InvoiceInterval;

use Illuminate\Database\Seeder;

class InvoiceIntervalSeeder extends Seeder {
    public function run() {
        InvoiceInterval::create(array(
                'name' => 'Weekly',
                'num_days' => 7
        ));
        InvoiceInterval::create(array(
                'name' => 'Bi-Weekly',
                'num_days' => 14
        ));
        InvoiceInterval::create(array(
                'name' => 'Monthly',
                'num_months' => 1
        ));
    }
}
