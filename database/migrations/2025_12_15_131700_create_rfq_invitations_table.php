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
        Schema::create('rfq_invitations', function (Blueprint $table) {
            $table->id();
            
            // Связь с RFQ
            $table->foreignId('rfq_id')->constrained()->onDelete('cascade');
            
            // Приглашённая компания
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            
            // Пользователь, отправивший приглашение
            $table->foreignId('invited_by')->constrained('users')->onDelete('cascade');
            
            // Статус приглашения
            $table->enum('status', ['pending', 'accepted', 'declined'])->default('pending');
            
            // Временные метки
            $table->timestamps();
            
            // Индексы
            $table->index('rfq_id');
            $table->index('company_id');
            
            // Уникальность: одна компания = одно приглашение на RFQ
            $table->unique(['rfq_id', 'company_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rfq_invitations');
    }
};