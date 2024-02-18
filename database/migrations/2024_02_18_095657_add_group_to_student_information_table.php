<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGroupToStudentInformationTable extends Migration
{
    public function up()
    {
        Schema::table('student_information', function (Blueprint $table) {
            $table->string('group')->nullable();
        });
    }

    public function down()
    {
        Schema::table('student_information', function (Blueprint $table) {
            $table->dropColumn('group');
        });
    }
}
