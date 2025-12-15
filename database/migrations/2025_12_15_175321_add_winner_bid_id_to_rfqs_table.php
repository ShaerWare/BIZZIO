<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rfqs', function (Blueprint $table) {
            $table->foreignId('winner_bid_id')
                ->nullable()
                ->after('status')
                ->constrained('rfq_bids')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('rfqs', function (Blueprint $table) {
            $table->dropForeign(['winner_bid_id']);
            $table->dropColumn('winner_bid_id');
        });
    }
};