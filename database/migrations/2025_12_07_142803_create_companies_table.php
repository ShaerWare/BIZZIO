<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Название компании
            $table->string('inn', 12)->unique(); // ИНН (уникальный)
            $table->string('legal_form')->nullable(); // ООО, АО, ИП и т.д.
            $table->string('logo')->nullable(); // Путь к логотипу
            $table->text('short_description')->nullable(); // Краткое описание
            $table->longText('full_description')->nullable(); // Полное описание
            $table->foreignId('industry_id')->nullable()->constrained()->nullOnDelete(); // Отрасль
            $table->boolean('is_verified')->default(false); // Верификация (админом)
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete(); // Создатель
            $table->timestamps();
            $table->softDeletes(); // Мягкое удаление

            // Индексы
            $table->index('inn');
            $table->index('is_verified');
            $table->index('industry_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};