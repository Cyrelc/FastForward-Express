<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ModifyUserAddRoleForeign extends Migration {
    public function up() {
        Schema::table('users', function($table) {
            $table->integer('role_id')->unsigned()->default(0);
            $table->foreign('role_id')
                    ->references('id')->on('roles');
        });
    }

    public function down() {
        Schema::table('users', function($table) {
            $table->dropForeign(['role_id']);
        });
    }
}

