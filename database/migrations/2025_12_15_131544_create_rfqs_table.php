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
        Schema::create('rfqs', function (Blueprint $table) {
            $table->id();
            
            // Уникальный номер (К-ГГММДД-0001)
            $table->string('number', 50)->unique();
            
            // Основная информация
            $table->string('title');
            $table->text('description')->nullable();
            
            // Организатор (компания)
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            
            // Создатель (пользователь)
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            
            // Тип процедуры
            $table->enum('type', ['open', 'closed'])->default('open'); // открытая/закрытая
            
            // Сроки
            $table->timestamp('start_date'); // Дата начала приёма заявок
            $table->timestamp('end_date'); // Дата окончания приёма заявок
            
            // Критерии оценки (веса в %)
            $table->decimal('weight_price', 5, 2)->default(50.00); // Вес "Цена" (0-100%)
            $table->decimal('weight_deadline', 5, 2)->default(30.00); // Вес "Срок выполнения"
            $table->decimal('weight_advance', 5, 2)->default(20.00); // Вес "Размер аванса"
            
            // Статус
            $table->enum('status', ['draft', 'active', 'closed', 'cancelled'])->default('draft');
            
            // Победитель (после закрытия)
            $table->foreignId('winner_bid_id')->nullable()->constrained('rfq_bids')->onDelete('set null');
            
            // Временные метки
            $table->timestamps();
            $table->softDeletes();
            
            // Индексы
            $table->index('company_id');
            $table->index('created_by');
            $table->index('status');
            $table->index('end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rfqs');
    }
};