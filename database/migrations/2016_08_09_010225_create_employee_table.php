<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->increments('employee_id');
            $table->string('employee_number')->nullable();
            $table->date('start_date');
            $table->unsignedInteger('contact_id');
			$table->unsignedInteger('user_id');
            $table->string('sin');
			$table->date('dob');
            $table->boolean('active')->default(true);
            
            $table->unique('employee_number');
            $table->foreign('contact_id')->references('contact_id')->on('contacts');
            $table->foreign('user_id')->references('user_id')->on('users');
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('employees');
    }
}
