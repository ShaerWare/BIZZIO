<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * One-time cleanup for #143: before the duplicate-listener fix, every
 * notification was stored twice. Remove the duplicates, keeping one row
 * per identical (notifiable, type, data) group.
 *
 * Safe and idempotent: on a clean/empty table it deletes nothing.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            // uuid supports ">" ordering, but not MIN() — use a self-join.
            DB::statement('
                DELETE FROM notifications a
                USING notifications b
                WHERE a.id > b.id
                  AND a.notifiable_id = b.notifiable_id
                  AND a.notifiable_type = b.notifiable_type
                  AND a.type = b.type
                  AND a.data = b.data
            ');
        } else {
            // sqlite / others: keep the lowest rowid per group.
            DB::statement('
                DELETE FROM notifications
                WHERE rowid NOT IN (
                    SELECT MIN(rowid) FROM notifications
                    GROUP BY notifiable_id, notifiable_type, type, data
                )
            ');
        }
    }

    public function down(): void
    {
        // Deleted duplicates cannot be restored.
    }
};
