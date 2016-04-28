<?php

use App\Permission;

use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder {
    public function run() {
        Permission::create(array('name' => 'customer_add'));
        Permission::create(array('name' => 'customer_delete'));
        Permission::create(array('name' => 'customer_modify'));

        Permission::create(array('name' => 'driver_add'));
        Permission::create(array('name' => 'driver_delete'));
        Permission::create(array('name' => 'driver_modify'));

        Permission::create(array('name' => 'bill_add'));
        Permission::create(array('name' => 'bill_delete'));
        Permission::create(array('name' => 'bill_modify'));
    }
}
