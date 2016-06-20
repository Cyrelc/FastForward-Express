<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInvoicesTable extends Migration {
    public function up() {
        Schema::create('invoices', function($table) {
            $table->increments('id');

            $table->integer('number')->unsigned();
            $table->date('date');

            /*

            REM: I'm not sure how we want to address
                'balance on account' and
                'outstanding balance'
            We might want to address it on a per-company
            bases, at least for the balance on account.

            */

            $table->date('printed_on')->nullable();

            $table->string('comment');

            $table->integer('creator_id')->unsigned()->nullable();
            $table->foreign('creator_id')
                    ->references('id')
                    ->on('users');

            $table->integer('last_modified_by_id')->unsigned()->nullable();
            $table->foreign('last_modified_by_id')
                    ->references('id')
                    ->on('users');


            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('invoices');
    }
}

