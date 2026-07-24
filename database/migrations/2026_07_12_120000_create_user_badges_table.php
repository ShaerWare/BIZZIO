<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Бейджи (ачивки) пользователей: цветная рамка + подпись, назначаемые админом.
 * У одного пользователя может быть несколько бейджей.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('color', 20);              // финальный HEX, напр. #dc3545
            $table->string('label')->nullable();      // подпись; null/'' = рамка без подписи
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_badges');
    }
};
