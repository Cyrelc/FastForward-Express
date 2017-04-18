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
            $table->unsignedInteger('manifest_id')->nullable();
            $table->unsignedInteger('invoice_id')->nullable();
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('reference_id');
            $table->unsignedInteger('driver_id');
            $table->unsignedInteger('interliner_id')->nullable();
            $table->string('bill_number');
            $table->string('description');
            $table->date('date');
            $table->decimal('amount');
            $table->decimal('taxes');
            $table->boolean('is_manifested')->default(false);
            $table->boolean('is_invoiced')->default(false);
            $table->decimal('interliner_amount')->nullable();

			$table->unique('bill_number');
			$table->foreign('manifest_id')->references('manifest_id')->on('manifests');
			$table->foreign('invoice_id')->references('invoice_id')->on('invoices');
			$table->foreign('account_id')->references('account_id')->on('accounts');
			$table->foreign('reference_id')->references('reference_id')->on('references');
			$table->foreign('driver_id')->references('driver_id')->on('drivers');
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
