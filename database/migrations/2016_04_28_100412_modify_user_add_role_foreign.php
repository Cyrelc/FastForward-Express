<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ModifyUserAddRoleForeign extends Migration {
    public function up() {
        Schema::table('users', function($table) {
            $table->integer('role_id')->unsigned();
            $table->foreign('role_id')
                    ->references('id')->on('roles');
        });
    }

    public function down() {
        Schema::table('users', function($table) {
            $table->dropForeign('users_role_id_foreign');
            $table->drop('role_id');
        });
    }
}

