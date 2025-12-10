<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            
            // Основная информация
            $table->string('name'); // Название проекта
            $table->string('slug')->unique(); // URL-friendly имя
            $table->text('description')->nullable(); // Краткое описание
            $table->longText('full_description')->nullable(); // Полное описание
            
            // Аватар (обложка проекта)
            $table->string('avatar')->nullable(); // Путь к изображению
            
            // Сроки
            $table->date('start_date')->nullable(); // Дата начала
            $table->date('end_date')->nullable(); // Дата окончания
            $table->boolean('is_ongoing')->default(false); // Проект продолжается по настоящее время
            
            // Статус
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            
            // Компания-заказчик (владелец проекта)
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            
            // Создатель проекта (пользователь)
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Индексы
            $table->index('company_id');
            $table->index('status');
            $table->index('created_by');
            $table->index('start_date');
            $table->index('end_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};