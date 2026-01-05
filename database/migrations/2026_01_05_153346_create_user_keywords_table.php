<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_keywords', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('keyword'); // Ключевое слово
            $table->timestamps();

            // Индексы
            $table->index('user_id');
            $table->unique(['user_id', 'keyword']); // Уникальность: 1 пользователь не может добавить одно слово дважды
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_keywords');
    }
};