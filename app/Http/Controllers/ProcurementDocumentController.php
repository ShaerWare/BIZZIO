<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Auction;
use App\Models\Rfq;
use App\Services\ProcurementDocumentsService;
use App\Support\ProcurementDocuments;
use App\Traits\HasProcurementDocuments;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * #185 Скачивание конкурсной документации (одним архивом или по файлу) с контролем доступа.
 */
class ProcurementDocumentController extends Controller
{
    public function __construct(private ProcurementDocumentsService $service) {}

    public function downloadRfqArchive(Rfq $rfq): BinaryFileResponse
    {
        return $this->archive($rfq, 'Документация_'.$rfq->number);
    }

    public function downloadAuctionArchive(Auction $auction): BinaryFileResponse
    {
        return $this->archive($auction, 'Документация_'.$auction->number);
    }

    public function downloadRfqFile(Rfq $rfq, Media $media): BinaryFileResponse
    {
        return $this->file($rfq, $media);
    }

    public function downloadAuctionFile(Auction $auction, Media $media): BinaryFileResponse
    {
        return $this->file($auction, $media);
    }

    /**
     * @param  Model&HasProcurementDocuments  $model
     */
    private function archive(Model $model, string $name): BinaryFileResponse
    {
        abort_unless($model->documentsAccessibleBy(auth()->user()), 403, 'Доступ к документации закрыт.');

        $zipPath = $this->service->buildZip($model, $name);
        abort_if($zipPath === null, 404, 'Документы отсутствуют.');

        return response()->download($zipPath, $name.'.zip')->deleteFileAfterSend(true);
    }

    /**
     * @param  Model&HasProcurementDocuments  $model
     */
    private function file(Model $model, Media $media): BinaryFileResponse
    {
        abort_unless($model->documentsAccessibleBy(auth()->user()), 403, 'Доступ к документации закрыт.');

        // Файл должен принадлежать этой процедуре и быть частью конкурсной документации.
        $belongs = $media->model_type === $model->getMorphClass()
            && (int) $media->model_id === (int) $model->getKey()
            && array_key_exists($media->collection_name, ProcurementDocuments::COLLECTIONS);

        abort_unless($belongs && is_file($media->getPath()), 404);

        return response()->download($media->getPath(), $media->file_name);
    }
}
