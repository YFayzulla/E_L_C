<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * `student_information` is the group-membership history shown on the student
     * detail page. It could only ever record joins — a departure had to be faked
     * by prefixing the group NAME string, which then renders to end users.
     *
     * 0 = qo'shildi (joined), 1 = chiqdi (left). Existing rows are all joins.
     */
    public function up()
    {
        Schema::table('student_information', function (Blueprint $table) {
            if (! Schema::hasColumn('student_information', 'action')) {
                $table->unsignedTinyInteger('action')->default(0)->after('group');
            }
        });

        Schema::table('student_information', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'student_information_user_created_idx');
        });
    }

    public function down()
    {
        Schema::table('student_information', function (Blueprint $table) {
            $table->dropIndex('student_information_user_created_idx');
        });

        Schema::table('student_information', function (Blueprint $table) {
            if (Schema::hasColumn('student_information', 'action')) {
                $table->dropColumn('action');
            }
        });
    }
};
