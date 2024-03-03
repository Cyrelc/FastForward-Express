<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAccountUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('account_users', function (Blueprint $table) {
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('contact_id');
            $table->foreignId('user_id');
            $table->boolean('is_primary')->default(true);

			$table->foreign('account_id')->references('account_id')->on('accounts');
            $table->foreign('contact_id')->references('contact_id')->on('contacts');
            $table->foreign('user_id')->references('id')->on('users');
			$table->primary(array('account_id', 'contact_id'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('account_users');
    }
}
