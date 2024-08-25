<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('assessments', function (Blueprint $table) {
            // Convert the column type to integer without losing data
            $table->integer('get_mark')->change();
        });
    }

    public function down()
    {
        Schema::table('assessments', function (Blueprint $table) {
            // Revert back to the original type if needed
            $table->string('get_mark')->change();
        });
    }

};
