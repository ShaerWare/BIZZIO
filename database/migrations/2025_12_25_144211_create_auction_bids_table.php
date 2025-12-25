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
        Schema::create('auction_bids', function (Blueprint $table) {
            $table->id();
            
            // Связь с аукционом
            $table->foreignId('auction_id')->constrained()->onDelete('cascade');
            
            // Компания-участник
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            
            // Пользователь, подавший ставку
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Предложенная цена
            $table->decimal('price', 15, 2);
            
            // Обезличивание участника (4-символьный код)
            $table->string('anonymous_code', 4)->nullable();
            
            // Комментарий участника (опционально)
            $table->text('comment')->nullable();
            
            // Тип ставки
            $table->enum('type', ['initial', 'bid'])->default('initial');
            // initial - заявка на участие
            // bid - ставка в торгах
            
            // Статус ставки
            $table->enum('status', ['pending', 'accepted', 'rejected', 'winner'])->default('pending');
            
            // Временные метки
            $table->timestamps();
            $table->softDeletes();
            
            // Индексы
            $table->index('auction_id');
            $table->index('company_id');
            $table->index('user_id');
            $table->index('type');
            $table->index('status');
            $table->index('anonymous_code');
            $table->index('created_at'); // Для сортировки по времени ставки
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auction_bids');
    }
};