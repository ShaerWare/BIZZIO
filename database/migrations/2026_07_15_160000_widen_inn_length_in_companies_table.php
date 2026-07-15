<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Расширяем ИНН до 12 символов: у юрлиц 10 цифр, у ИП/физлиц — 12.
     * Ранее миграция 2025_12_22 ошибочно сузила колонку до 10, из-за чего
     * регистрация компании с 12-значным ИНН падала с 500 (varchar(10) too long),
     * хотя валидация (StoreCompanyRequest) 12 цифр допускает.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('inn', 12)->change();
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('inn', 10)->change();
        });
    }
};
