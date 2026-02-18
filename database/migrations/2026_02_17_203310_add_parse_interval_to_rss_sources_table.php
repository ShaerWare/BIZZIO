<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rss_sources', function (Blueprint $table) {
            $table->unsignedInteger('parse_interval')->default(15)->after('enabled');
        });
    }

    public function down(): void
    {
        Schema::table('rss_sources', function (Blueprint $table) {
            $table->dropColumn('parse_interval');
        });
    }
};
