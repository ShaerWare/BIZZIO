<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rfqs', function (Blueprint $table) {
            $table->string('currency', 3)->default('RUB')->after('type');
        });

        Schema::table('auctions', function (Blueprint $table) {
            $table->string('currency', 3)->default('RUB')->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('rfqs', function (Blueprint $table) {
            $table->dropColumn('currency');
        });

        Schema::table('auctions', function (Blueprint $table) {
            $table->dropColumn('currency');
        });
    }
};
