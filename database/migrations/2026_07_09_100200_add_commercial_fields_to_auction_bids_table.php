<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * #179 Коммерческий аукцион — предложения этапа 2 (три критерия) в auction_bids.
 * Добавляет значение 'offer' в тип ставки и колонки критериев/баллов.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auction_bids', function (Blueprint $table) {
            // Дополнительные критерии коммерческого предложения
            $table->integer('deadline')->nullable()->after('price');           // срок, дни
            $table->decimal('advance_percent', 5, 2)->nullable()->after('deadline'); // аванс, %

            // Баллы по критериям и итог
            $table->decimal('score_price', 8, 4)->nullable()->after('advance_percent');
            $table->decimal('score_deadline', 8, 4)->nullable()->after('score_price');
            $table->decimal('score_advance', 8, 4)->nullable()->after('score_deadline');
            $table->decimal('total_score', 8, 4)->nullable()->after('score_advance');

            // Базовое (первое) предложение участника на этапе 2
            $table->boolean('is_base')->default(false)->after('total_score');

            // Момент, когда предложение стало «Лучшим» (история лидеров)
            $table->timestamp('became_best_at')->nullable()->after('is_base');

            $table->index('became_best_at');
        });

        // Расширяем допустимые значения type: initial | bid | offer
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE auction_bids DROP CONSTRAINT IF EXISTS auction_bids_type_check');
            DB::statement("ALTER TABLE auction_bids ADD CONSTRAINT auction_bids_type_check CHECK (type::text IN ('initial', 'bid', 'offer'))");
        } else {
            // SQLite (тесты): перестраиваем колонку как обычную строку без CHECK
            Schema::table('auction_bids', function (Blueprint $table) {
                $table->string('type', 20)->default('initial')->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE auction_bids DROP CONSTRAINT IF EXISTS auction_bids_type_check');
            DB::statement("ALTER TABLE auction_bids ADD CONSTRAINT auction_bids_type_check CHECK (type::text IN ('initial', 'bid'))");
        }

        Schema::table('auction_bids', function (Blueprint $table) {
            $table->dropIndex(['became_best_at']);
            $table->dropColumn([
                'deadline',
                'advance_percent',
                'score_price',
                'score_deadline',
                'score_advance',
                'total_score',
                'is_base',
                'became_best_at',
            ]);
        });
    }
};
