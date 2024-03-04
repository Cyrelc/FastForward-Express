<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('users', function(Blueprint $table) {
            $table->id();
            $table->softDeletes();

            $table->string('email')->unique();
            $table->boolean('is_enabled')->default(true);
			$table->boolean('is_locked')->default(false);
			$table->smallInteger('login_attempts');
            $table->string('password');
            $table->string('username');
            $table->rememberToken();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('users');
    }
}
