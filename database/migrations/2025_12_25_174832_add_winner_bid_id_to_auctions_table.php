<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('auctions', function (Blueprint $table) {
            // Добавляем внешний ключ на auction_bids (теперь она уже существует)
            $table->foreign('winner_bid_id')
                ->references('id')
                ->on('auction_bids')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auctions', function (Blueprint $table) {
            $table->dropForeign(['winner_bid_id']);
        });
    }
};
 