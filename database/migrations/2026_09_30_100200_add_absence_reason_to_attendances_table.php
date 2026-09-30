<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            if (! Schema::hasColumn('attendances', 'reason')) {
                $table->text('reason')->nullable();
            }

            if (! Schema::hasColumn('attendances', 'reason_written_by')) {
                $table->unsignedBigInteger('reason_written_by')->nullable();
                $table->foreign('reason_written_by')->references('id')->on('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('attendances', 'reason_written_at')) {
                $table->timestamp('reason_written_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            if (Schema::hasColumn('attendances', 'reason_written_by')) {
                $table->dropForeign(['reason_written_by']);
            }

            foreach (['reason', 'reason_written_by', 'reason_written_at'] as $column) {
                if (Schema::hasColumn('attendances', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
