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

            $table->decimal('amount')->nullable();
            $table->string('bill_number')->nullable();
            $table->unsignedInteger('charge_account_id')->nullable();
            $table->string('charge_reference_value')->nullable();
            $table->unsignedInteger('chargeback_id')->nullable();
            $table->unsignedInteger('delivery_account_id')->nullable();
            $table->unsignedInteger('delivery_address_id');
            $table->float('delivery_driver_commission')->nullable();
            $table->unsignedInteger('delivery_driver_id')->nullable();
            $table->unsignedInteger('delivery_manifest_id')->nullable();
            $table->string('delivery_reference_value')->nullable();
            $table->string('delivery_type')->nullable();
            $table->string('description');
            $table->string('incomplete_fields')->nullable();
            $table->decimal('interliner_cost')->nullable();
            $table->decimal('interliner_cost_to_customer')->nullable();
            $table->unsignedInteger('interliner_id')->nullable();
            $table->string('interliner_reference_value')->nullable();
            $table->unsignedInteger('invoice_id')->nullable();
            $table->boolean('is_min_weight_size')->default(0);
            $table->boolean('is_pallet')->default(0);
            $table->text('packages')->nullable();
            $table->unsignedInteger('payment_id')->nullable();
            $table->unsignedInteger('payment_type_id');
            $table->float('percentage_complete');
            $table->unsignedInteger('pickup_account_id')->nullable();
            $table->unsignedInteger('pickup_address_id');
            $table->float('pickup_driver_commission')->nullable();
            $table->unsignedInteger('pickup_driver_id')->nullable();
            $table->unsignedInteger('pickup_manifest_id')->nullable();
            $table->string('pickup_reference_value')->nullable();
            $table->text('price_line_items');
            $table->boolean('skip_invoicing')->default(false);
            $table->datetime('time_call_received');
            $table->datetime('time_delivered')->nullable();
            $table->datetime('time_delivery_scheduled');
            $table->datetime('time_dispatched')->nullable();
            $table->datetime('time_picked_up')->nullable();
            $table->datetime('time_pickup_scheduled');
            $table->boolean('use_imperial')->default(false);

            $table->unique('bill_number');
            
            $table->foreign('charge_account_id')->references('account_id')->on('accounts');
            $table->foreign('chargeback_id')->references('chargeback_id')->on('chargebacks');
			$table->foreign('delivery_account_id')->references('account_id')->on('accounts');
			$table->foreign('delivery_address_id')->references('address_id')->on('addresses');
            $table->foreign('delivery_driver_id')->references('employee_id')->on('employees');
            $table->foreign('delivery_manifest_id')->references('manifest_id')->on('manifests');
			$table->foreign('interliner_id')->references('interliner_id')->on('interliners');
			$table->foreign('invoice_id')->references('invoice_id')->on('invoices');
            $table->foreign('payment_id')->references('payment_id')->on('payments');
            $table->foreign('payment_type_id')->references('payment_type_id')->on('payment_types');
			$table->foreign('pickup_account_id')->references('account_id')->on('accounts');
			$table->foreign('pickup_address_id')->references('address_id')->on('addresses');
			$table->foreign('pickup_driver_id')->references('employee_id')->on('employees');
            $table->foreign('pickup_manifest_id')->references('manifest_id')->on('manifests');
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
