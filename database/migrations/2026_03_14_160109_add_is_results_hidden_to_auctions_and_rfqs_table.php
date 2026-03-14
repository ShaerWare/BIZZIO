<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auctions', function (Blueprint $table) {
            $table->boolean('is_results_hidden')->default(false)->after('status');
        });

        Schema::table('rfqs', function (Blueprint $table) {
            $table->boolean('is_results_hidden')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('auctions', function (Blueprint $table) {
            $table->dropColumn('is_results_hidden');
        });

        Schema::table('rfqs', function (Blueprint $table) {
            $table->dropColumn('is_results_hidden');
        });
    }
};
