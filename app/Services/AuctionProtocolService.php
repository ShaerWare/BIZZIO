<?php

namespace App\Services;

use App\Models\Auction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class AuctionProtocolService
{
    /**
     * Генерация PDF-протокола подведения итогов
     */
    public function generate(Auction $auction): ?string
    {
        try {
            // Загружаем связанные данные
            $auction->load([
                'company',
                'creator',
                'tradingBids.company',
                'winnerBid.company'
            ]);
            
            // Генерируем PDF
            $pdf = Pdf::loadView('pdf.auction-protocol', [
                'auction' => $auction,
                'bids' => $auction->tradingBids, // Все ставки в торгах
                'winner' => $auction->winnerBid,
            ]);
            
            // Настройки PDF
            $pdf->setPaper('A4', 'portrait');
            
            // Сохраняем в Media Library
            $filename = 'protocol_' . $auction->number . '.pdf';
            $content = $pdf->output();
            
            $auction->addMediaFromString($content)
                ->usingFileName($filename)
                ->toMediaCollection('protocol');
            
            Log::info("Протокол для аукциона {$auction->number} успешно сгенерирован.");
            
            return $filename;
            
        } catch (\Exception $e) {
            Log::error("Ошибка при генерации протокола для аукциона {$auction->number}: " . $e->getMessage());
            
            return null;
        }
    }
}