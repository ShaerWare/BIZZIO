<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rss_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Название источника (CNews, RBC и т.д.)
            $table->string('url'); // URL RSS-ленты
            $table->boolean('enabled')->default(true); // Включён ли источник
            $table->timestamp('last_parsed_at')->nullable(); // Время последнего парсинга
            $table->timestamps();
            $table->softDeletes();

            // Индексы
            $table->index('enabled');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rss_sources');
    }
};