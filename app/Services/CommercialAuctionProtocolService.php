<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Auction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

/**
 * #179 Генерация итогового протокола коммерческого аукциона (этап 2).
 */
class CommercialAuctionProtocolService
{
    public function generate(Auction $auction): ?string
    {
        try {
            $auction->load([
                'company',
                'creator',
                'winnerBid.company',
            ]);

            // История лучших предложений (по возрастанию времени лидерства).
            $history = $auction->offerBids()->with('company')->get();

            $pdf = Pdf::loadView('pdfs.commercial-auction-protocol', [
                'auction' => $auction,
                'history' => $history,
                'winner' => $auction->winnerBid,
            ]);

            $pdf->setPaper('A4', 'portrait');

            $filename = 'protocol_'.$auction->number.'.pdf';
            $auction->addMediaFromString($pdf->output())
                ->usingFileName($filename)
                ->toMediaCollection('protocol');

            Log::info("Протокол коммерческого аукциона {$auction->number} сгенерирован.");

            return $filename;
        } catch (\Exception $e) {
            Log::error("Ошибка генерации протокола коммерческого аукциона {$auction->number}: ".$e->getMessage());

            return null;
        }
    }
}
