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
            $table->float('account_balance')->default(0);
            $table->string('account_number');
            $table->boolean('active')->default(true);
            $table->unsignedInteger('billing_address_id')->nullable();
            $table->boolean('can_be_parent')->default(true);
            $table->string('custom_field')->nullable();
            $table->float('discount')->nullable();
            $table->boolean('gst_exempt')->default(false);
            $table->string('invoice_interval');
            $table->text('invoice_comment')->nullable();
            $table->text('invoice_sort_order');
            // $table->string('stripe_id')->nullable();
            $table->float('min_invoice_amount')->default(0);
            $table->string('name');
            $table->unsignedInteger('parent_account_id')->nullable();
            $table->unsignedInteger('ratesheet_id')->nullable();
            $table->boolean('send_bills')->default(true);
            $table->boolean('send_email_invoices')->default(true);
            $table->boolean('send_paper_invoices')->default(false);
            $table->unsignedInteger('shipping_address_id');
            $table->date('start_date');
            // $table->boolean('charge_interest')->default(true);
			// $table->float('fuel_surcharge');
            $table->boolean('use_parent_ratesheet')->default(0);

			$table->unique('account_number');
			$table->foreign('billing_address_id')->references('address_id')->on('addresses');
            $table->foreign('shipping_address_id')->references('address_id')->on('addresses');
            $table->foreign('parent_account_id')->references('account_id')->on('accounts');
            $table->foreign('ratesheet_id')->references('ratesheet_id')->on('ratesheets');
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
