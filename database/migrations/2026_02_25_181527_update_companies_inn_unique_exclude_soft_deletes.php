<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replace full unique on inn/slug with partial unique (excluding soft-deleted).
     * PostgreSQL supports WHERE in CREATE UNIQUE INDEX.
     * SQLite (tests) does not — skip for non-pgsql drivers.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // INN: partial unique excluding soft-deleted
        DB::statement('ALTER TABLE companies DROP CONSTRAINT IF EXISTS companies_inn_unique');
        DB::statement('DROP INDEX IF EXISTS companies_inn_unique');
        DB::statement('CREATE UNIQUE INDEX companies_inn_unique ON companies (inn) WHERE deleted_at IS NULL');

        // Slug: partial unique excluding soft-deleted
        DB::statement('ALTER TABLE companies DROP CONSTRAINT IF EXISTS companies_slug_unique');
        DB::statement('DROP INDEX IF EXISTS companies_slug_unique');
        DB::statement('CREATE UNIQUE INDEX companies_slug_unique ON companies (slug) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS companies_inn_unique');
        DB::statement('CREATE UNIQUE INDEX companies_inn_unique ON companies (inn)');

        DB::statement('DROP INDEX IF EXISTS companies_slug_unique');
        DB::statement('CREATE UNIQUE INDEX companies_slug_unique ON companies (slug)');
    }
};
