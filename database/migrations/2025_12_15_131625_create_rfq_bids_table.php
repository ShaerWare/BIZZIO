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
        Schema::create('rfq_bids', function (Blueprint $table) {
            $table->id();
            
            // Связь с RFQ
            $table->foreignId('rfq_id')->constrained()->onDelete('cascade');
            
            // Компания-участник
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            
            // Пользователь, подавший заявку
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Предложение участника
            $table->decimal('price', 15, 2); // Цена (руб. без НДС)
            $table->integer('deadline'); // Срок выполнения (календарные дни)
            $table->decimal('advance_percent', 5, 2)->default(0); // Размер аванса (%)
            
            // Комментарий участника
            $table->text('comment')->nullable();
            
            // Рассчитанные баллы (заполняются автоматически)
            $table->decimal('score_price', 8, 4)->default(0); // Баллы за цену
            $table->decimal('score_deadline', 8, 4)->default(0); // Баллы за срок
            $table->decimal('score_advance', 8, 4)->default(0); // Баллы за аванс
            $table->decimal('total_score', 8, 4)->default(0); // Итоговый балл
            
            // Статус заявки
            $table->enum('status', ['pending', 'accepted', 'rejected', 'winner'])->default('pending');
            
            // Временные метки
            $table->timestamps();
            $table->softDeletes();
            
            // Индексы
            $table->index('rfq_id');
            $table->index('company_id');
            $table->index('user_id');
            $table->index('status');
            
            // Уникальность: одна компания = одна заявка на RFQ
            $table->unique(['rfq_id', 'company_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rfq_bids');
    }
};