<?php

namespace App\Http\Controllers;

use App\Support\ProcurementDocuments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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
        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();

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
     * #185 Временная загрузка файла конкурсной документации (PDF).
     * Сохраняет файл при ошибке валидации формы создания/редактирования процедуры.
     */
    public function storeProcurement(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf', 'max:20480'], // ≤ 20 МБ, только PDF
            'collection' => ['required', 'string', Rule::in(array_keys(ProcurementDocuments::COLLECTIONS))],
        ], [
            'file.mimes' => 'Только формат PDF.',
            'file.max' => 'Файл не должен превышать 20 МБ.',
        ]);

        $file = $request->file('file');
        $collection = $request->input('collection');

        $temp = ProcurementDocuments::tempFiles();

        // Проверка суммарного объёма (≤ 20 МБ) с учётом уже загруженных temp-файлов.
        if (ProcurementDocuments::tempTotalSize() + $file->getSize() > ProcurementDocuments::MAX_TOTAL_BYTES) {
            return response()->json([
                'success' => false,
                'message' => 'Общий объём конкурсной документации не должен превышать 20 МБ.',
            ], 422);
        }

        $filename = Str::uuid().'.pdf';
        $path = $file->storeAs(ProcurementDocuments::TEMP_DIR, $filename, 'local');

        $meta = [
            'id' => (string) Str::uuid(),
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
        ];

        if ($collection === 'other_documents') {
            $list = $temp['other_documents'] ?? [];
            $list[] = $meta;
            $temp['other_documents'] = $list;
        } else {
            // Одиночная коллекция — заменяем, старый temp-файл удаляем.
            if (! empty($temp[$collection]['path'])) {
                Storage::disk('local')->delete($temp[$collection]['path']);
            }
            $temp[$collection] = $meta;
        }

        session([ProcurementDocuments::TEMP_SESSION_KEY => $temp]);

        return response()->json([
            'success' => true,
            'id' => $meta['id'],
            'filename' => $meta['original_name'],
            'size' => $this->formatBytes($meta['size']),
            'collection' => $collection,
        ]);
    }

    /**
     * #185 Удаление временного файла конкурсной документации.
     */
    public function destroyProcurement(Request $request)
    {
        $collection = $request->input('collection');
        $id = $request->input('id');

        $temp = ProcurementDocuments::tempFiles();

        if (! isset($temp[$collection])) {
            return response()->json(['success' => true]);
        }

        if ($collection === 'other_documents') {
            $list = $temp['other_documents'] ?? [];
            foreach ($list as $i => $file) {
                if (($file['id'] ?? null) === $id) {
                    Storage::disk('local')->delete($file['path']);
                    unset($list[$i]);
                }
            }
            $temp['other_documents'] = array_values($list);
            if (empty($temp['other_documents'])) {
                unset($temp['other_documents']);
            }
        } else {
            if (! empty($temp[$collection]['path'])) {
                Storage::disk('local')->delete($temp[$collection]['path']);
            }
            unset($temp[$collection]);
        }

        session([ProcurementDocuments::TEMP_SESSION_KEY => $temp]);

        return response()->json(['success' => true]);
    }

    /**
     * Форматирование размера файла
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1).' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    }
}
