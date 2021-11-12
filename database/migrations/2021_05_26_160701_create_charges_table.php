<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChargesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('charges', function (Blueprint $table) {
            $table->increments('charge_id');
            $table->timestamps();
            $table->unsignedInteger('bill_id');
            $table->unsignedInteger('charge_account_id')->nullable()->default(null);
            $table->unsignedInteger('charge_employee_id')->nullable()->default(null);
            $table->string('charge_reference_value')->nullable()->default(null);
            $table->unsignedInteger('charge_type_id');

            $table->foreign('charge_account_id')->references('account_id')->on('accounts');
            $table->foreign('bill_id')->references('bill_id')->on('bills');
            $table->foreign('charge_type_id')->references('payment_type_id')->on('payment_types');
            $table->foreign('charge_employee_id')->references('employee_id')->on('employees');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('charges');
    }
}
