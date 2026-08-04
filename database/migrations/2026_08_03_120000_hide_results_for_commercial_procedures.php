<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * #237 У коммерческого аукциона результаты скрыты всегда (галочки в форме больше нет).
 * Приводим ранее созданные процедуры к этому правилу.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('rfqs')->where('procedure', 'commercial')->update(['is_results_hidden' => true]);
        DB::table('auctions')->where('procedure', 'commercial')->update(['is_results_hidden' => true]);
    }

    public function down(): void
    {
        // Обратной операции нет: прежнее значение флага не сохранялось.
    }
};
