<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLineItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('line_items', function (Blueprint $table) {
            $table->increments('line_item_id');

            $table->timestamps();

            $table->unsignedInteger('amendment_number')->nullable()->default(null);
            $table->unsignedInteger('charge_id');
            $table->unsignedInteger('chargeback_id')->nullable()->default(null);
            $table->unsignedInteger('delivery_driver_id')->nullable()->default(null);
            $table->unsignedInteger('delivery_manifest_id')->nullable()->default(null);
            $table->decimal('driver_amount');
            $table->unsignedInteger('invoice_id')->nullable()->default(null);
            $table->string('name');
            $table->unsignedInteger('pickup_driver_id')->nullable()->default(null);
            $table->unsignedInteger('pickup_manifest_id')->nullable()->default(null);
            $table->decimal('price');
            $table->string('type');

            $table->foreign('charge_id')->references('charge_id')->on('charges');
            $table->foreign('chargeback_id')->references('chargeback_id')->on('chargebacks');
            $table->foreign('delivery_driver_id')->references('employee_id')->on('employees');
            $table->foreign('delivery_manifest_id')->references('manifest_id')->on('manifests');
            $table->foreign('invoice_id')->references('invoice_id')->on('invoices');
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
        //
    }
}
