<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_project', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            
            // Роль участника в проекте
            $table->enum('role', [
                'customer',        // Заказчик
                'general_contractor', // Генподрядчик
                'contractor',      // Подрядчик
                'supplier',        // Поставщик
                'consultant'       // Консультант
            ])->default('contractor');
            
            // Описание участия
            $table->text('participation_description')->nullable();
            
            // Отзывы и оценки (будут реализованы позже)
            $table->text('customer_review')->nullable(); // Отзыв от заказчика
            $table->unsignedTinyInteger('customer_rating')->nullable(); // Оценка от заказчика (1-5)
            $table->text('participant_review')->nullable(); // Отзыв от участника
            $table->unsignedTinyInteger('participant_rating')->nullable(); // Оценка от участника (1-5)
            
            $table->timestamps();
            
            // Уникальность: одна компания не может иметь две одинаковые роли в одном проекте
            $table->unique(['company_id', 'project_id', 'role']);
            
            // Индексы
            $table->index('company_id');
            $table->index('project_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_project');
    }
};