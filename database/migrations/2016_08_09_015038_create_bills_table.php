<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBillsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bills', function (Blueprint $table) {
            $table->increments('bill_id');
            $table->unsignedInteger('pickup_manifest_id')->nullable();
            $table->unsignedInteger('delivery_manifest_id')->nullable();
            $table->unsignedInteger('invoice_id')->nullable();
            $table->unsignedInteger('charge_account_id')->nullable();
            $table->unsignedInteger('pickup_account_id')->nullable();
            $table->unsignedInteger('delivery_account_id')->nullable();
            $table->unsignedInteger('pickup_address_id');
            $table->unsignedInteger('delivery_address_id');
            $table->string('charge_reference_value')->nullable();
            $table->string('pickup_reference_value')->nullable();
            $table->string('delivery_reference_value')->nullable();
            $table->unsignedInteger('pickup_driver_id')->nullable();
            $table->unsignedInteger('delivery_driver_id')->nullable();
            $table->float('pickup_driver_commission')->nullable();
            $table->float('delivery_driver_commission')->nullable();
            $table->unsignedInteger('interliner_id')->nullable();
            $table->decimal('interliner_cost_to_customer')->nullable();
            $table->decimal('interliner_cost')->nullable();
            $table->string('interliner_reference_value')->nullable();
            $table->boolean('skip_invoicing')->default(false);
            $table->string('bill_number')->nullable();
            $table->string('description');
            $table->datetime('time_pickup_scheduled');
            $table->datetime('time_delivery_scheduled');
            $table->decimal('amount')->nullable();
            $table->string('delivery_type')->nullable();
            $table->datetime('time_call_received');
            $table->datetime('time_dispatched')->nullable();
            $table->datetime('time_picked_up')->nullable();
            $table->datetime('time_delivered')->nullable();
            $table->float('percentage_complete');

			$table->unique('bill_number');
            $table->foreign('pickup_manifest_id')->references('manifest_id')->on('manifests');
            $table->foreign('delivery_manifest_id')->references('manifest_id')->on('manifests');
			$table->foreign('invoice_id')->references('invoice_id')->on('invoices');
			$table->foreign('charge_account_id')->references('account_id')->on('accounts');
			$table->foreign('pickup_account_id')->references('account_id')->on('accounts');
			$table->foreign('delivery_account_id')->references('account_id')->on('accounts');
			$table->foreign('pickup_address_id')->references('address_id')->on('addresses');
			$table->foreign('delivery_address_id')->references('address_id')->on('addresses');
			$table->foreign('pickup_driver_id')->references('employee_id')->on('employees');
            $table->foreign('delivery_driver_id')->references('employee_id')->on('employees');
			$table->foreign('interliner_id')->references('interliner_id')->on('interliners');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('bills');
    }
}
