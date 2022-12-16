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
            $table->string('account_number')->unique();
            $table->boolean('active')->default(true);
            $table->unsignedInteger('billing_address_id')->nullable();
            $table->boolean('can_be_parent')->default(true);
            $table->string('custom_field')->nullable();
            $table->float('discount')->nullable();
            $table->boolean('gst_exempt')->default(false);
            $table->string('invoice_interval');
            $table->text('invoice_comment')->nullable();
            $table->text('invoice_sort_order');
            $table->boolean('is_custom_field_mandatory')->default(false);
            $table->float('min_invoice_amount')->default(0);
            $table->fullText('name')->unique();
            $table->unsignedInteger('parent_account_id')->nullable();
            $table->smallInteger('pm_last_four')->nullable();
            $table->string('pm_type')->nullable();
            $table->unsignedInteger('ratesheet_id')->nullable();
            $table->boolean('send_bills')->default(true);
            $table->boolean('send_email_invoices')->default(true);
            $table->boolean('send_paper_invoices')->default(false);
            $table->unsignedInteger('shipping_address_id');
            $table->boolean('show_invoice_line_items')->default(false);
            $table->date('start_date');
            $table->string('stripe_id')->nullable()->index();
            // $table->boolean('charge_interest')->default(true);
			// $table->float('fuel_surcharge');

			$table->foreign('billing_address_id')->references('address_id')->on('addresses');
            $table->foreign('shipping_address_id')->references('address_id')->on('addresses');
            $table->foreign('parent_account_id')->references('account_id')->on('accounts');
            $table->foreign('ratesheet_id')->references('ratesheet_id')->on('ratesheets');

            $table->timestamps();
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
