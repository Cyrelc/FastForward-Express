<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder {
    public function run() {
        //Static values
        $this->call(SelectionsTableSeeder::class);        
        $this->call(RolesTableSeeder::class);
        $this->call(ExpiriesTableSeeder::class);
        // $this->call(PaymentTypesTableSeeder::class);

        $this->call(UsersTableSeeder::class);

        $this->call(InvoiceSortOptionsSeeder::class);
        $this->call(DriversTableSeeder::class);
        $this->call(AccountsTableSeeder::class);
        $this->call(CommissionsTableSeeder::class);
        $this->call(InterlinersTableSeeder::class);
        $this->call(BillsTableSeeder::class);
    }
}
