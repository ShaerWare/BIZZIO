<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * F3: Временная загрузка файлов для сохранения при ошибке валидации
 */
class TempUploadController extends Controller
{
    /**
     * Загрузить файл во временное хранилище
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'max:20480'], // max 20MB
            'collection' => ['required', 'string', 'in:technical_specification,documents,logo'],
        ]);

        $file = $request->file('file');
        $collection = $request->input('collection');

        // Генерируем уникальное имя
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

        // Сохраняем во временную папку
        $path = $file->storeAs('temp-uploads', $filename, 'local');

        // Сохраняем информацию в сессию
        $tempFiles = session('temp_uploads', []);
        $tempFiles[$collection] = [
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'uploaded_at' => now()->toISOString(),
        ];
        session(['temp_uploads' => $tempFiles]);

        return response()->json([
            'success' => true,
            'filename' => $file->getClientOriginalName(),
            'size' => $this->formatBytes($file->getSize()),
            'collection' => $collection,
        ]);
    }

    /**
     * Удалить временный файл
     */
    public function destroy(Request $request)
    {
        $collection = $request->input('collection');

        $tempFiles = session('temp_uploads', []);

        if (isset($tempFiles[$collection])) {
            // Удаляем файл
            Storage::disk('local')->delete($tempFiles[$collection]['path']);

            // Удаляем из сессии
            unset($tempFiles[$collection]);
            session(['temp_uploads' => $tempFiles]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Получить информацию о временных файлах
     */
    public function index()
    {
        $tempFiles = session('temp_uploads', []);

        $result = [];
        foreach ($tempFiles as $collection => $file) {
            $result[$collection] = [
                'filename' => $file['original_name'],
                'size' => $this->formatBytes($file['size']),
            ];
        }

        return response()->json($result);
    }

    /**
     * Форматирование размера файла
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }
}
