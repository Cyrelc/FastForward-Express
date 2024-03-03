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

            $table->string('bill_number')->nullable();
            $table->unsignedInteger('created_by');
            $table->unsignedInteger('delivery_account_id')->nullable();
            $table->unsignedInteger('delivery_address_id');
            $table->float('delivery_driver_commission')->nullable();
            $table->unsignedInteger('delivery_driver_id')->nullable();
            $table->string('delivery_person_name')->nullable();
            $table->string('delivery_reference_value')->nullable();
            $table->string('delivery_type')->nullable();
            $table->text('description');
            $table->text('incomplete_fields')->nullable();
            $table->float('interliner_cost')->nullable();
            $table->unsignedInteger('interliner_id')->nullable();
            $table->string('interliner_reference_value')->nullable();
            $table->text('internal_comments')->nullable();
            $table->boolean('is_min_weight_size')->default(0);
            $table->boolean('is_pallet')->default(0);
            $table->boolean('is_template')->default(0);
            $table->text('packages')->nullable();
            $table->integer('percentage_complete');
            $table->unsignedInteger('pickup_account_id')->nullable();
            $table->unsignedInteger('pickup_address_id');
            $table->float('pickup_driver_commission')->nullable();
            $table->unsignedInteger('pickup_driver_id')->nullable();
            $table->string('pickup_person_name')->nullable();
            $table->string('pickup_reference_value')->nullable();
            $table->boolean('proof_of_delivery_required')->default(0);
            $table->unsignedInteger('repeat_interval')->nullable()->default(null);
            $table->boolean('skip_invoicing')->default(false);
            $table->datetime('time_call_received');
            $table->datetime('time_delivered')->nullable()->default(null);
            $table->datetime('time_delivery_scheduled');
            $table->datetime('time_dispatched')->nullable()->default(null);
            $table->datetime('time_picked_up')->nullable()->default(null);
            $table->datetime('time_pickup_scheduled');
            $table->datetime('time_ten_foured')->nullable()->default(null);
            $table->boolean('use_imperial')->default(false);

            $table->timestamps();
            $table->unique('bill_number');
            
            $table->foreign('created_by')->references('id')->on('users');
			$table->foreign('delivery_account_id')->references('account_id')->on('accounts');
			$table->foreign('delivery_address_id')->references('address_id')->on('addresses');
            $table->foreign('delivery_driver_id')->references('employee_id')->on('employees');
			$table->foreign('interliner_id')->references('interliner_id')->on('interliners');
			$table->foreign('pickup_account_id')->references('account_id')->on('accounts');
			$table->foreign('pickup_address_id')->references('address_id')->on('addresses');
			$table->foreign('pickup_driver_id')->references('employee_id')->on('employees');
            $table->foreign('repeat_interval')->references('selection_id')->on('selections');
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
