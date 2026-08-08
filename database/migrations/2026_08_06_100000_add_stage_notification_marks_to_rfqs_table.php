<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * #195 Отметки об отправленных уведомлениях о старте этапов коммерческого аукциона.
 * Хранятся на запросе цен (этап 1): уведомление о скором старте этапа 2 уходит за 30 минут,
 * а аукцион этапа 2 к этому моменту может быть ещё не создан (при коротком зазоре между
 * окончанием приёма и началом торгов — по умолчанию 10 минут, #222).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rfqs', function (Blueprint $table) {
            $table->timestamp('stage1_notified_at')->nullable()->after('linked_auction_id');
            $table->timestamp('stage2_notified_at')->nullable()->after('stage1_notified_at');
        });

        // Существующие процедуры не должны получить «задним числом» пачку уведомлений:
        // помечаем отправленными те события, момент которых уже прошёл.
        $now = now();

        DB::table('rfqs')
            ->where('procedure', 'commercial')
            ->where('start_date', '<=', $now)
            ->update(['stage1_notified_at' => $now]);

        DB::table('rfqs')
            ->where('procedure', 'commercial')
            ->whereNotNull('trading_start')
            ->where('trading_start', '<=', $now->copy()->addMinutes(30))
            ->update(['stage2_notified_at' => $now]);
    }

    public function down(): void
    {
        Schema::table('rfqs', function (Blueprint $table) {
            $table->dropColumn(['stage1_notified_at', 'stage2_notified_at']);
        });
    }
};
