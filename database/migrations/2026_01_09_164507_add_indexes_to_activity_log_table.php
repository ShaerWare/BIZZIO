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
        Schema::table('activity_log', function (Blueprint $table) {
            // Индексы для производительности
            $table->index('created_at');
            $table->index(['subject_type', 'subject_id']);
            $table->index(['causer_type', 'causer_id']);
            
            // Составной индекс для частых запросов
            $table->index(['subject_type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['subject_type', 'subject_id']);
            $table->dropIndex(['causer_type', 'causer_id']);
            $table->dropIndex(['subject_type', 'created_at']);
        });
    }
};