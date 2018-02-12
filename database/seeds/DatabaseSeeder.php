<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder {
    public function run() {
        //Static values
        $this->call(SelectionsTableSeeder::class);        
        $this->call(RolesTableSeeder::class);
        $this->call(RateTypesTableSeeder::class);
        $this->call(ExpiriesTableSeeder::class);
        $this->call(PaymentMethodsTableSeeder::class);

        $this->call(UsersTableSeeder::class);

        $this->call(DriversTableSeeder::class);
        $this->call(AccountsTableSeeder::class);
        $this->call(CommissionsTableSeeder::class);
        $this->call(InterlinersTableSeeder::class);
        $this->call(BillsTableSeeder::class);
        $this->call(ManifestsTableSeeder::class);
    }
}
