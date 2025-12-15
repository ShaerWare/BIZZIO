<?php

namespace App\Jobs;

use App\Models\Rfq;
use App\Services\RfqScoringService;
use App\Services\RfqProtocolService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CloseRfqJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;

    protected Rfq $rfq;

    /**
     * Create a new job instance.
     */
    public function __construct(Rfq $rfq)
    {
        $this->rfq = $rfq;
    }

    /**
     * Execute the job.
     */
    public function handle(
        RfqScoringService $scoringService,
        RfqProtocolService $protocolService
    ): void
    {
        try {
            // 1. Расчёт баллов для всех заявок
            $scoringService->calculateScores($this->rfq);

            // 2. Определение победителя
            $winner = $scoringService->determineWinner($this->rfq);

            if ($winner) {
                // 3. Обновление статуса победителя
                $winner->update(['status' => 'winner']);

                // 4. Обновление RFQ
                $this->rfq->update([
                    'status' => 'closed',
                    'winner_bid_id' => $winner->id,
                ]);

                // 5. Генерация PDF-протокола
                $protocolService->generateProtocol($this->rfq);

                Log::info("RFQ #{$this->rfq->number} закрыт. Победитель: {$winner->company->name}");

                // TODO: Отправка уведомлений участникам (Спринт 7)
            } else {
                // Нет заявок
                $this->rfq->update(['status' => 'closed']);
                Log::warning("RFQ #{$this->rfq->number} закрыт без заявок");
            }
        } catch (\Exception $e) {
            Log::error("Ошибка при закрытии RFQ #{$this->rfq->number}: " . $e->getMessage());
            throw $e;
        }
    }
}