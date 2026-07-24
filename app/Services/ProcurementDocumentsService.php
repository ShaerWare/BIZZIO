<?php

declare(strict_types=1);

namespace App\Services;

use App\Traits\HasProcurementDocuments;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\HasMedia;
use ZipArchive;

/**
 * #185 Прикрепление и выгрузка конкурсной документации (Запрос цен / Аукцион / Ком. аукцион).
 *
 * - Прикрепляет загруженные PDF к соответствующим media-коллекциям (со сжатием при загрузке).
 * - Собирает все документы процедуры в один ZIP-архив для пакетного скачивания.
 */
class ProcurementDocumentsService
{
    public function __construct(private PdfCompressionService $compressor) {}

    /**
     * Прикрепить документы из запроса к модели (Rfq|Auction).
     * Одиночные коллекции заменяются при наличии нового файла; «Прочие» — добавляются.
     *
     * @param  HasMedia&\Illuminate\Database\Eloquent\Model  $model
     */
    public function attachFromRequest(HasMedia $model, Request $request): void
    {
        foreach (HasProcurementDocuments::SINGLE_DOCUMENT_COLLECTIONS as $collection) {
            if ($request->hasFile($collection)) {
                $file = $request->file($collection);
                $this->compressor->compressInPlace($file->getRealPath());
                $model->clearMediaCollection($collection);
                $model->addMedia($file->getRealPath())
                    ->usingFileName($this->pdfFileName($file->getClientOriginalName()))
                    ->toMediaCollection($collection);
            }
        }

        if ($request->hasFile('other_documents')) {
            foreach ($request->file('other_documents') as $file) {
                $this->compressor->compressInPlace($file->getRealPath());
                $model->addMedia($file->getRealPath())
                    ->usingFileName($this->pdfFileName($file->getClientOriginalName()))
                    ->toMediaCollection('other_documents');
            }
        }
    }

    /**
     * Собрать все документы процедуры в ZIP и вернуть путь к временному архиву.
     * Возвращает null, если документов нет или ZIP-расширение недоступно.
     *
     * @param  HasMedia&HasProcurementDocuments  $model
     */
    public function buildZip(HasMedia $model, string $archiveName): ?string
    {
        $documents = $model->allProcurementDocuments();

        if ($documents->isEmpty() || ! class_exists(ZipArchive::class)) {
            return null;
        }

        $zipPath = tempnam(sys_get_temp_dir(), 'docs_').'.zip';
        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return null;
        }

        // Метки коллекций для человекочитаемых имён внутри архива.
        $labels = HasProcurementDocuments::DOCUMENT_COLLECTIONS;
        $used = [];

        foreach ($documents as $media) {
            $sourcePath = $media->getPath();
            if (! is_file($sourcePath)) {
                continue;
            }

            $label = $labels[$media->collection_name] ?? 'Документ';
            $entry = $label.' - '.$media->file_name;

            // Гарантируем уникальность имён в архиве.
            $unique = $entry;
            $i = 1;
            while (isset($used[$unique])) {
                $unique = $label.' ('.(++$i).') - '.$media->file_name;
            }
            $used[$unique] = true;

            $zip->addFile($sourcePath, $unique);
        }

        $zip->close();

        return $zipPath;
    }

    /**
     * Нормализовать имя файла: не пустое, с расширением .pdf.
     */
    private function pdfFileName(string $original): string
    {
        $name = trim($original) !== '' ? trim($original) : 'document.pdf';

        if (! str_ends_with(strtolower($name), '.pdf')) {
            $name .= '.pdf';
        }

        return $name;
    }
}
