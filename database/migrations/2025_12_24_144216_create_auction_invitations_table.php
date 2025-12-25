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
        Schema::create('auction_invitations', function (Blueprint $table) {
            $table->id();
            
            // Связь с аукционом
            $table->foreignId('auction_id')->constrained()->onDelete('cascade');
            
            // Приглашённая компания
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            
            // Статус приглашения
            $table->enum('status', ['pending', 'accepted', 'declined'])->default('pending');
            
            // Временные метки
            $table->timestamps();
            
            // Индексы
            $table->index('auction_id');
            $table->index('company_id');
            $table->index('status');
            
            // Уникальность: одна компания = одно приглашение на аукцион
            $table->unique(['auction_id', 'company_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auction_invitations');
    }
};