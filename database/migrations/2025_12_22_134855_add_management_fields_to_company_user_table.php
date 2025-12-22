<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_user', function (Blueprint $table) {
            $table->foreignId('added_by')->nullable()->after('role')->constrained('users')->onDelete('set null');
            $table->timestamp('added_at')->nullable()->after('added_by');
            $table->boolean('can_manage_moderators')->default(false)->after('added_at');
        });
    }

    public function down(): void
    {
        Schema::table('company_user', function (Blueprint $table) {
            $table->dropForeign(['added_by']);
            $table->dropColumn(['added_by', 'added_at', 'can_manage_moderators']);
        });
    }
};