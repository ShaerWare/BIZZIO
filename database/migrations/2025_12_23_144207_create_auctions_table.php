<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('auctions', function (Blueprint $table) {
            $table->id();
            
            // Уникальный номер (А-ГГММДД-0001)
            $table->string('number', 50)->unique();
            
            // Основная информация
            $table->string('title');
            $table->text('description')->nullable();
            
            // Компания-организатор
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            
            // Создатель аукциона
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            
            // Тип процедуры (открытая/закрытая)
            $table->enum('type', ['open', 'closed'])->default('open');
            
            // Сроки приёма заявок и проведения торгов
            $table->timestamp('start_date'); // Начало приёма заявок
            $table->timestamp('end_date');   // Окончание приёма заявок
            $table->timestamp('trading_start')->nullable(); // Начало торгов
            $table->timestamp('trading_end')->nullable();   // Окончание торгов
            
            // Начальная (максимальная) цена
            $table->decimal('starting_price', 15, 2);
            
            // Шаг аукциона (в процентах: 0.5 - 5%)
            $table->decimal('step_percent', 5, 2)->default(1.00);
            
            // Время последней ставки (для автозакрытия через 20 мин)
            $table->timestamp('last_bid_at')->nullable();
            
            // Статус аукциона
            $table->enum('status', ['draft', 'active', 'trading', 'closed', 'cancelled'])->default('draft');
            
            // ⚠️ ВРЕМЕННО: winner_bid_id будет добавлен в отдельной миграции
            $table->unsignedBigInteger('winner_bid_id')->nullable();
            
            // Временные метки
            $table->timestamps();
            $table->softDeletes();
            
            // Индексы
            $table->index('number');
            $table->index('company_id');
            $table->index('created_by');
            $table->index('status');
            $table->index('last_bid_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auctions');
    }
};