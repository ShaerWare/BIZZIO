<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_comments', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            $table->text('body'); // Текст комментария
            
            // Для древовидной структуры комментариев (опционально, на будущее)
            $table->foreignId('parent_id')->nullable()->constrained('project_comments')->onDelete('cascade');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Индексы
            $table->index('project_id');
            $table->index('user_id');
            $table->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_comments');
    }
};