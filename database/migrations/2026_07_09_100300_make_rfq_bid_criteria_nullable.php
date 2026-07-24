<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * #179 На этапе 1 коммерческого аукциона заявка содержит только цену,
 * поэтому срок и аванс должны допускать NULL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rfq_bids', function (Blueprint $table) {
            $table->integer('deadline')->nullable()->change();
            $table->decimal('advance_percent', 5, 2)->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('rfq_bids', function (Blueprint $table) {
            $table->integer('deadline')->nullable(false)->change();
            $table->decimal('advance_percent', 5, 2)->default(0)->change();
        });
    }
};
