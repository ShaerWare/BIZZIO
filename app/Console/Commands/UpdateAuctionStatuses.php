<?php

namespace App\Console\Commands;

use App\Jobs\UpdateAuctionStatuses as UpdateAuctionStatusesJob;
use Illuminate\Console\Command;

class UpdateAuctionStatuses extends Command
{
    protected $signature = 'auctions:update-statuses';

    protected $description = 'Обновить статусы аукционов на основе текущего времени';

    /**
     * Делегируем всю логику единственной реализации — джобе UpdateAuctionStatuses.
     *
     * #222 Раньше команда дублировала логику джобы и разошлась с ней: её блок 1 не исключал
     * коммерческие аукционы и по 0 initialBids отменял коммерческий этап 2 при старте торгов.
     * Чтобы фиксы больше не расходились между планировщиком (команда) и джобой — держим одну
     * реализацию в App\Jobs\UpdateAuctionStatuses, а команда лишь вызывает её.
     */
    public function handle(): int
    {
        $this->info('🔄 Обновление статусов аукционов…');

        (new UpdateAuctionStatusesJob)->handle();

        $this->info('✅ Готово (см. лог для деталей).');

        return 0;
    }
}
