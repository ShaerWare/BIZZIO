<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Company;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Добавляем колонку как nullable
        Schema::table('companies', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
        });

        // 2. Заполняем slug для существующих компаний
        Company::chunk(100, function ($companies) {
            foreach ($companies as $company) {
                $slug = Str::slug($company->name);
                $originalSlug = $slug;
                $counter = 1;

                // Проверка уникальности
                while (Company::where('slug', $slug)->where('id', '!=', $company->id)->exists()) {
                    $slug = $originalSlug . '-' . $counter;
                    $counter++;
                }

                $company->update(['slug' => $slug]);
            }
        });

        // 3. Делаем колонку NOT NULL и добавляем уникальный индекс
        Schema::table('companies', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->unique()->change();
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex(['slug']);
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};