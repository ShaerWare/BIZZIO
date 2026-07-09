<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * #179 Коммерческий аукцион — этап 2 (runtime).
 * Аукцион с procedure='commercial' связан с RFQ этапа 1 и несёт веса/шаги/референсы.
 * Стандартные аукционы (procedure='standard') не затрагиваются.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auctions', function (Blueprint $table) {
            $table->string('procedure', 20)->default('standard')->after('type');

            // Связь с RFQ этапа 1
            $table->unsignedBigInteger('rfq_id')->nullable()->after('company_id');

            // Веса критериев (переносятся из RFQ; у стандартных аукционов NULL)
            $table->decimal('weight_price', 5, 2)->nullable()->after('step_percent');
            $table->decimal('weight_deadline', 5, 2)->nullable()->after('weight_price');
            $table->decimal('weight_advance', 5, 2)->nullable()->after('weight_deadline');

            // Шаги изменения критериев
            $table->decimal('step_price', 5, 2)->nullable()->after('weight_advance');
            $table->integer('step_deadline')->nullable()->after('step_price');
            $table->decimal('step_advance', 5, 2)->nullable()->after('step_deadline');

            // Референсы нормировки
            $table->integer('max_deadline')->nullable()->after('step_advance');
            $table->decimal('max_advance', 5, 2)->nullable()->after('max_deadline');

            // Текущее «Лучшее предложение» (принцип непрерывного лидерства)
            $table->unsignedBigInteger('best_bid_id')->nullable()->after('winner_bid_id');

            $table->index('procedure');
            $table->index('rfq_id');
        });
    }

    public function down(): void
    {
        Schema::table('auctions', function (Blueprint $table) {
            $table->dropIndex(['procedure']);
            $table->dropIndex(['rfq_id']);
            $table->dropColumn([
                'procedure',
                'rfq_id',
                'weight_price',
                'weight_deadline',
                'weight_advance',
                'step_price',
                'step_deadline',
                'step_advance',
                'max_deadline',
                'max_advance',
                'best_bid_id',
            ]);
        });
    }
};
