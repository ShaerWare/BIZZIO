<?php

namespace App\Jobs;

use App\Models\Auction;
use App\Services\AuctionWinnerService;
use App\Services\AuctionProtocolService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CloseAuctionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $auctionId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $auctionId)
    {
        $this->auctionId = $auctionId;
    }

    /**
     * Execute the job.
     */
    public function handle(
        AuctionWinnerService $winnerService,
        AuctionProtocolService $protocolService
    ): void
    {
        $auction = Auction::find($this->auctionId);
        
        if (!$auction) {
            Log::warning("CloseAuctionJob: Аукцион с ID {$this->auctionId} не найден.");
            return;
        }
        
        // Проверка: аукцион всё ещё в торгах
        if ($auction->status !== 'trading') {
            Log::info("CloseAuctionJob: Аукцион {$auction->number} уже закрыт или отменён.");
            return;
        }
        
        Log::info("CloseAuctionJob: Начало закрытия аукциона {$auction->number}");
        
        // Шаг 1: Определяем победителя
        $winner = $winnerService->determineWinner($auction);
        
        if (!$winner) {
            // Если нет ставок, закрываем без победителя
            $winnerService->closeWithoutWinner($auction);
        }

        // Обновляем модель из БД (winner_bid_id, status обновились в determineWinner)
        $auction->refresh();

        // Шаг 2: Генерируем PDF-протокол
        $protocolService->generate($auction);
        
        // Шаг 3: Отправляем уведомления (будет реализовано в Спринте 7)
        // TODO: Отправка уведомлений организатору и участникам
        
        Log::info("CloseAuctionJob: Аукцион {$auction->number} успешно закрыт.");
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("CloseAuctionJob: Ошибка при закрытии аукциона ID {$this->auctionId}: " . $exception->getMessage());
    }
}