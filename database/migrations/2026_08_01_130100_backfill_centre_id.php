<?php

use App\Models\Centre;
use App\Tenancy\TenantTables;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Step 2 of 3: point every existing row at the first centre.
 *
 * No cleverness is needed or wanted — everything in the database today belongs
 * to one centre by definition. Data only, no schema, and re-runnable.
 */
return new class extends Migration
{
    public function up(): void
    {
        $centreId = Centre::withoutGlobalScopes()->orderBy('id')->value('id');

        if ($centreId === null) {
            throw new RuntimeException(
                'Markaz topilmadi — avval backfill_first_centre migratsiyasi ishlashi kerak.'
            );
        }

        foreach (TenantTables::all() as $table) {
            $updated = DB::table($table)->whereNull('centre_id')->update(['centre_id' => $centreId]);

            if ($updated > 0) {
                echo "  {$table}: {$updated} ta qator markaz #{$centreId} ga biriktirildi\n";
            }
        }
    }

    public function down(): void
    {
        // Nothing to undo: the columns themselves go in the previous migration's
        // down(), and there is no earlier value to restore.
    }
};
