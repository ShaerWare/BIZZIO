<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * #179 Коммерческий аукцион — этап 1 (Запрос цен) хранит настройку этапа 2.
 * Все поля аддитивны: стандартные RFQ (procedure='standard') не затрагиваются.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rfqs', function (Blueprint $table) {
            // Дискриминатор процедуры
            $table->string('procedure', 20)->default('standard')->after('type');

            // Параметры этапа 2 (Коммерческий аукцион)
            $table->timestamp('trading_start')->nullable()->after('end_date'); // старт торгов
            $table->timestamp('trading_end')->nullable()->after('trading_start'); // окончание торгов

            // Шаги изменения критериев на этапе 2
            $table->decimal('step_price', 5, 2)->nullable()->after('weight_advance');   // %
            $table->integer('step_deadline')->nullable()->after('step_price');          // дни
            $table->decimal('step_advance', 5, 2)->nullable()->after('step_deadline');   // %

            // Референсы нормировки (организатор задаёт максимумы)
            $table->integer('max_deadline')->nullable()->after('step_advance');          // дни
            $table->decimal('max_advance', 5, 2)->nullable()->after('max_deadline');      // %

            // Связь с порождённым аукционом этапа 2
            $table->unsignedBigInteger('linked_auction_id')->nullable()->after('winner_bid_id');

            $table->index('procedure');
        });
    }

    public function down(): void
    {
        Schema::table('rfqs', function (Blueprint $table) {
            $table->dropIndex(['procedure']);
            $table->dropColumn([
                'procedure',
                'trading_start',
                'trading_end',
                'step_price',
                'step_deadline',
                'step_advance',
                'max_deadline',
                'max_advance',
                'linked_auction_id',
            ]);
        });
    }
};
