<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auctions', function (Blueprint $table) {
            $table->text('cancellation_reason')->nullable()->after('status');
        });
        Schema::table('rfqs', function (Blueprint $table) {
            $table->text('cancellation_reason')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('auctions', function (Blueprint $table) {
            $table->dropColumn('cancellation_reason');
        });
        Schema::table('rfqs', function (Blueprint $table) {
            $table->dropColumn('cancellation_reason');
        });
    }
};
