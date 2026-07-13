<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Zone regions are gone. A region was a single free-text string per zone that only ever fed the
 * conditional engine, where tags (many per zone, same free text) say everything it could say and
 * groups carry the rates. No zone had one set and no saved conditional referenced it.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('zones', function (Blueprint $table) {
            $table->dropColumn('region');
        });
    }

    public function down()
    {
        Schema::table('zones', function (Blueprint $table) {
            $table->string('region')->nullable();
        });
    }
};
