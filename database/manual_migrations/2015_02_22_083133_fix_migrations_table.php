<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        DB::statement('INSERT INTO migrations (migration, batch) VALUES ("2016_08_09_015036_create_payment_types_table", 1)');
        DB::statement('DELETE FROM migrations WHERE migration = "2019_10_09_202257_create_payment_types_table"');
        DB::statement('DELETE FROM migrations WHERE migration LIKE "%bills%"');
        DB::statement('INSERT INTO migrations (migration, batch) VALUES ("2019_10_15_015038_create_bills_table", 1)');
        DB::statement('INSERT INTO migrations (migration, batch) VALUES ("2022_05_03_000001_create_stripe_columns", 28)');
        DB::statement('INSERT INTO migrations (migration, batch) VALUES ("2019_05_03_000002_create_subscriptions_table", 29)');
        DB::statement('INSERT INTO migrations (migration, batch) VALUES ("2019_05_03_000003_create_subscription_items_table", 29)');
        DB::statement('INSERT into migrations (migration, batch) values ("2024_02_20_215244_create_cache_table", 28)');
        DB::statement('UPDATE model_has_permissions set model_type = "App\\\\Models\\\\User"');
        DB::statement('UPDATE account_users set user_id = 585 where user_id is null');
        // Clean up manually identified legacy users that were never properly initialized (and their settings where applicable)
        DB::statement('DELETE from user_settings where user_id in (3, 41, 44, 45, 77, 98, 297, 302, 409)');
        DB::statement('DELETE from users where user_id in (3, 41, 44, 45, 77, 98, 297, 302, 409)');
        DB::statement('UPDATE bills set pickup_reference_value = null where pickup_reference_value = ""');
        DB::statement('UPDATE bills set delivery_reference_value = null where delivery_reference_value = ""');
        DB::statement('UPDATE charges set charge_reference_value = null where charge_reference_value = ""');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        DB::statement('DELETE FROM migrations WHERE migration = "2016_08_09_015036_create_payment_types_table"');
        DB::statement('INSERT INTO migrations (migration, batch) VALUES ("2019_10_09_202257_create_payment_types_table", 1)');
        DB::statement('INSERT INTO migrations (migration, batch) VALUES ("2016_08_09_015038_create_bills_table", 1)');
        DB::statement('DELETE FROM migrations WHERE migration = "2016_08_09_015038_create_bills_table"');
        DB::statement('DELETE FROM migrations WHERE migration = "2022_05_03_000001_create_stripe_columns"');
        DB::statement('DELETE FROM migrations WHERE migration = "2019_05_03_000002_create_subscriptions_table"');
        DB::statement('DELETE FROM migrations WHERE migration = "2019_05_03_000003_create_subscription_items_table"');
        DB::statement('ALTER table payments drop column error');
        DB::statement('ALTER TABLE model_has_permissions set model_type = "App\\Models\\User"');
    }
};
