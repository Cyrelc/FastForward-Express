<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->increments('account_id');
            $table->unsignedInteger('rate_type_id');
            $table->unsignedInteger('billing_address_id')->nullable();
            $table->unsignedInteger('shipping_address_id');
            $table->string('account_number');
            $table->string('invoice_interval');
			$table->text('invoice_comment');
            $table->string('stripe_id')->nullable();
            $table->string('name');
            $table->date('start_date');
            $table->boolean('send_bills')->default(false);
            $table->boolean('send_invoices')->default(false);
            $table->boolean('has_parent')->default(false);
            $table->unsignedInteger('parent_account_id')->nullable();
            $table->boolean('has_discount')->default(false);
            $table->float('discount');
            $table->boolean('gst_exempt')->default(false);
            $table->boolean('charge_interest')->default(true);
			$table->float('fuel_surcharge');
            $table->boolean('can_be_parent')->default(true);
            $table->boolean('uses_custom_field')->default(false);
            $table->string('custom_field')->nullable();
            $table->boolean('active')->default(true);

			$table->unique('account_number');
			$table->foreign('rate_type_id')->references('rate_type_id')->on('rate_types');
			$table->foreign('billing_address_id')->references('address_id')->on('addresses');
            $table->foreign('shipping_address_id')->references('address_id')->on('addresses');
			$table->foreign('parent_account_id')->references('account_id')->on('accounts');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('accounts');
    }
}
