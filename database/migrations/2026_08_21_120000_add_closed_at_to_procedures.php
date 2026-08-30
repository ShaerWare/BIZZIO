<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * #296 Момент завершения процедуры — точка отсчёта срока хранения конкурсной документации.
 *
 * Раньше срок считался от `updated_at`, но его сдвигает любое изменение процедуры
 * (генерация протокола, правка полей), из-за чего документы жили дольше положенного.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['rfqs', 'auctions'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->timestamp('closed_at')->nullable()->after('status');
            });

            // Для уже завершённых процедур берём updated_at — лучшего приближения нет.
            DB::table($table)
                ->whereIn('status', ['closed', 'cancelled'])
                ->update(['closed_at' => DB::raw('updated_at')]);
        }
    }

    public function down(): void
    {
        foreach (['rfqs', 'auctions'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn('closed_at');
            });
        }
    }
};
