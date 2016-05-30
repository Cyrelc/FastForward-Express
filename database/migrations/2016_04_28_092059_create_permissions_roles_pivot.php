<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePermissionsRolesPivot extends Migration {
    public function up() {
        Schema::create('permission_role', function(Blueprint $table) {
            $table->increments('id');
            $table->integer('permission_id')->unsigned();
            $table->integer('role_id')->unsigned();
            $table->foreign('permission_id')
                    ->references('id')
                    ->on('permissions');
            $table->foreign('role_id')
                    ->references('id')
                    ->on('roles');
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('permission_role');
    }
}
