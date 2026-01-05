<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rss_source_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('link')->unique(); // Уникальность для предотвращения дублей
            $table->string('image')->nullable(); // URL изображения
            $table->timestamp('published_at')->nullable(); // Дата публикации из RSS
            $table->timestamps();
            $table->softDeletes();

            // Индексы
            $table->index('rss_source_id');
            $table->index('published_at');
            $table->index('created_at');
            
            // FULLTEXT индекс для быстрого поиска по ключевым словам
            $table->fullText(['title', 'description']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news');
    }
};