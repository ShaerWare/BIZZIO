<?php

namespace App\Services;

use App\Models\Rfq;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class RfqProtocolService
{
    /**
     * Генерация PDF-протокола подведения итогов
     */
    public function generateProtocol(Rfq $rfq): string
    {
        // Данные для PDF
        $data = [
            'rfq' => $rfq->load(['company', 'bids.company', 'winnerBid.company']),
            'generatedAt' => now()->format('d.m.Y H:i'),
        ];

        // Генерация PDF
        $pdf = Pdf::loadView('pdfs.rfq-protocol', $data);

        // Сохранение в storage
        $filename = 'protocol_' . $rfq->number . '_' . now()->timestamp . '.pdf';
        $path = 'rfq-protocols/' . $filename;
        
        Storage::disk('public')->put($path, $pdf->output());

        // Привязка к Media Library
        $rfq->addMediaFromDisk($path, 'public')
            ->toMediaCollection('protocol');

        return $path;
    }
}