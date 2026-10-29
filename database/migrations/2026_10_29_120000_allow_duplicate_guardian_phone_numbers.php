<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * A guardian is reused by phone and linked to each child through
     * parent_student. Therefore no users-table index needs to be removed:
     * the older 2026_07_27 migration already removed the legacy
     * parents_tel UNIQUE index, and users.phone remains safe to keep unique.
     *
     * This migration previously attempted to drop indexes by assumed names.
     * Production schemas can have different names (or the older migration may
     * already have removed them), which made it fail. Keeping it as a safe
     * no-op lets every existing deployment complete its migration history.
     */
    public function up(): void
    {
        // Intentionally no schema change. See the migration note above.
    }

    /**
     * No schema change was made in up().
     */
    public function down(): void
    {
        // Intentionally empty.
    }
};
