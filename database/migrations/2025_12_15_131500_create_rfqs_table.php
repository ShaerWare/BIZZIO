<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
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
            $table->enum('type', ['open', 'closed'])->default('open');
            
            // Сроки
            $table->timestamp('start_date');
            $table->timestamp('end_date');
            
            // Критерии оценки (веса в %)
            $table->decimal('weight_price', 5, 2)->default(50.00);
            $table->decimal('weight_deadline', 5, 2)->default(30.00);
            $table->decimal('weight_advance', 5, 2)->default(20.00);
            
            // Статус
            $table->enum('status', ['draft', 'active', 'closed', 'cancelled'])->default('draft');
            
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

    public function down(): void
    {
        Schema::dropIfExists('rfqs');
    }
};