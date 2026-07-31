<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * `parents_tel` was created UNIQUE, which makes it impossible to register
     * two siblings under the same parent phone number. Now that a parent can
     * legitimately own several students, the constraint has to go.
     *
     * The index name is looked up rather than assumed, because the column was
     * altered once already (2026_01_13) and dbal may have renamed it.
     */
    public function up()
    {
        foreach ($this->uniqueIndexesOnParentsTel() as $indexName) {
            $this->dropIndex($indexName);
        }

        // Keep lookups fast — we resolve parents by this number.
        if (! $this->indexExists('users_parents_tel_index')) {
            Schema::table('users', function ($table) {
                $table->index('parents_tel', 'users_parents_tel_index');
            });
        }
    }

    public function down()
    {
        if ($this->indexExists('users_parents_tel_index')) {
            Schema::table('users', function ($table) {
                $table->dropIndex('users_parents_tel_index');
            });
        }

        // Not restored as UNIQUE on purpose: existing rows may now share a value
        // and re-adding the constraint would fail.
    }

    /**
     * @return array<int, string>
     */
    private function uniqueIndexesOnParentsTel(): array
    {
        return array_keys(array_filter(
            $this->indexes(),
            fn($index) => $index->isUnique() && in_array('parents_tel', $index->getColumns(), true)
        ));
    }

    private function indexExists(string $name): bool
    {
        return array_key_exists($name, $this->indexes());
    }

    /**
     * Index metadata via dbal, so this works on MySQL and SQLite alike.
     *
     * @return array<string, \Doctrine\DBAL\Schema\Index>
     */
    private function indexes(): array
    {
        return DB::connection()
            ->getDoctrineSchemaManager()
            ->listTableIndexes('users');
    }

    private function dropIndex(string $name): void
    {
        // Primary keys are never dropped here; guard just in case.
        if (strtolower($name) === 'primary') {
            return;
        }

        DB::connection()
            ->getDoctrineSchemaManager()
            ->dropIndex($name, 'users');
    }
};
