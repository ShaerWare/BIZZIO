<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * #218 Участник процедуры (Запрос цен / Аукцион): обезличенный код для чата
 * и отстранение от участия организатором.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procedure_participants', function (Blueprint $table) {
            $table->id();
            $table->morphs('procedure'); // Rfq | Auction
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            // Обезличенный код участника В ЧАТЕ. Умышленно отличается по формату от
            // anonymous_code торгов этапа 2, чтобы переписку нельзя было сопоставить со ставками.
            $table->string('chat_code', 8);

            // Отстранение от участия (#218)
            $table->timestamp('banned_at')->nullable();
            $table->text('ban_reason')->nullable();
            $table->foreignId('banned_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['procedure_type', 'procedure_id', 'company_id'], 'procedure_participants_unique');
            $table->unique(['procedure_type', 'procedure_id', 'chat_code'], 'procedure_participants_code_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procedure_participants');
    }
};
