<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder {
    public function run() {
        $this->call(PermissionSeeder::class);
        $this->call(RoleSeeder::class);
        $this->call(PaymentMethodSeeder::class);
        $this->call(ReferenceTypeSeeder::class);
        $this->call(RateTypeSeeder::class);
        $this->call(InvoiceIntervalSeeder::class);

        $this->call(UserSeeder::class);

        $this->call(DriverSeeder::class);
        $this->call(CustomerSeeder::class);

        $this->call(BillSeeder::class);
    }
}
