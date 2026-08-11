<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * #218 Сообщения чата процедуры (вопросы участников и ответы организатора).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procedure_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->morphs('procedure'); // Rfq | Auction
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();

            // Сообщение организатора видно всем как «Организатор», участника — под обезличенным кодом.
            $table->boolean('is_organizer')->default(false);
            // Системная запись (например, отстранение участника) — публикуется от имени площадки.
            $table->boolean('is_system')->default(false);

            $table->text('body');
            $table->timestamps();

            $table->index(['procedure_type', 'procedure_id', 'id'], 'procedure_chat_messages_feed_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procedure_chat_messages');
    }
};
