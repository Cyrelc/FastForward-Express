<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateManifestModificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('manifest_modifications', function (Blueprint $table) {
            $table->unsignedInteger('manifest_id');
            $table->unsignedInteger('modification_id');

			$table->foreign('manifest_id')->references('manifest_id')->on('manifests');
			$table->foreign('modification_id')->references('modification_id')->on('modifications');
			$table->primary(array('manifest_id', 'modification_id'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('manifest_modifications');
    }
}
