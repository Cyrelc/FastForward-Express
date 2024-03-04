<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        DB::statement('ALTER table account_users drop foreign key account_users_ibfk_1');
        DB::statement('ALTER table bills drop foreign key bills_ibfk_2');
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        Schema::table('user_settings', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('user_id', 'id');
        });
        Schema::table('account_users', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users');
        });
        Schema::table('bills', function (Blueprint $table) {
            $table->foreign('created_by')->references('id')->on('users');
        });
        Schema::table('employees', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users');
        });
        Schema::table('user_settings', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
