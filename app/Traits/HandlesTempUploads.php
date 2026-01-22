<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\HasMedia;

/**
 * F3: Trait для обработки временных файлов
 */
trait HandlesTempUploads
{
    /**
     * Добавить файл к модели из temp-хранилища или из запроса
     */
    protected function addFileToModel(HasMedia $model, Request $request, string $fieldName, string $collection): void
    {
        // Сначала проверяем temp-файл
        $tempKey = $fieldName . '_temp';
        if ($request->filled($tempKey)) {
            $tempCollection = $request->input($tempKey);
            $tempFile = session('temp_uploads.' . $tempCollection);

            if ($tempFile && Storage::disk('local')->exists($tempFile['path'])) {
                $fullPath = Storage::disk('local')->path($tempFile['path']);

                $model->addMedia($fullPath)
                    ->usingFileName($tempFile['original_name'])
                    ->toMediaCollection($collection);

                // Очищаем temp из сессии
                $tempFiles = session('temp_uploads', []);
                unset($tempFiles[$tempCollection]);
                session(['temp_uploads' => $tempFiles]);

                return;
            }
        }

        // Если нет temp-файла, проверяем обычный upload
        if ($request->hasFile($fieldName)) {
            $model->addMediaFromRequest($fieldName)
                ->toMediaCollection($collection);
        }
    }

    /**
     * Очистить все temp-файлы пользователя
     */
    protected function clearTempUploads(): void
    {
        $tempFiles = session('temp_uploads', []);

        foreach ($tempFiles as $file) {
            if (isset($file['path'])) {
                Storage::disk('local')->delete($file['path']);
            }
        }

        session()->forget('temp_uploads');
    }
}
