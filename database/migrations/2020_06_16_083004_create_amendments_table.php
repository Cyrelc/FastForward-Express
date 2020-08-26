<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAmendmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('amendments', function (Blueprint $table) {
            $table->increments('amendment_id');

            $table->decimal('amount');
            $table->unsignedInteger('bill_id');
            $table->string('description');
            $table->unsignedInteger('invoice_id');
            // $table->boolean('finalized')->default(false);
            // $table->unsignedInteger('manifest_id');

            $table->foreign('bill_id')->references('bill_id')->on('bills');
            $table->foreign('invoice_id')->references('invoice_id')->on('invoices');
            // $table->foreign('manifest_id')->references('manifest_id')->on('manifests');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('amendments');
    }
}
