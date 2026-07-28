<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * #185 Константы конкурсной документации (Запрос цен / Аукцион / Ком. аукцион).
 *
 * Вынесены в отдельный класс, т.к. константы трейта нельзя обращать напрямую
 * по имени трейта (ограничение PHP 8.2). Этот класс — единый источник истины.
 */
final class ProcurementDocuments
{
    /** Коллекции документов: ключ = media-коллекция, значение = человекочитаемая метка. */
    public const COLLECTIONS = [
        'notice' => 'Извещение',
        'technical_specification' => 'Техническое задание (ТЗ)',
        'contract_draft' => 'Проект договора',
        'other_documents' => 'Прочие файлы',
    ];

    /** Одиночные коллекции (ровно один файл). */
    public const SINGLE_COLLECTIONS = ['notice', 'technical_specification', 'contract_draft'];

    /** Общий лимит объёма конкурсной документации (байты) — 20 МБ. */
    public const MAX_TOTAL_BYTES = 20 * 1024 * 1024;

    /**
     * #185 Ключ сессии для временно загруженных файлов конкурсной документации.
     * Позволяет сохранить прикреплённые PDF при ошибке валидации формы,
     * чтобы пользователю не пришлось прикреплять их повторно.
     */
    public const TEMP_SESSION_KEY = 'temp_procurement_docs';

    /** Папка для временных файлов на диске local. */
    public const TEMP_DIR = 'temp-procurement';

    /**
     * Временно загруженные файлы из сессии.
     * Структура: ['notice' => [meta], 'other_documents' => [[meta], ...], ...].
     *
     * @return array<string, mixed>
     */
    public static function tempFiles(): array
    {
        return session(self::TEMP_SESSION_KEY, []);
    }

    /**
     * Есть ли временно загруженный файл в указанной коллекции.
     */
    public static function hasTemp(string $collection): bool
    {
        $temp = self::tempFiles();

        return $collection === 'other_documents'
            ? ! empty($temp['other_documents'])
            : ! empty($temp[$collection]);
    }

    /**
     * Суммарный размер всех временно загруженных файлов (байты).
     */
    public static function tempTotalSize(): int
    {
        $total = 0;

        foreach (self::tempFiles() as $collection => $entry) {
            $items = $collection === 'other_documents' ? $entry : [$entry];
            foreach ($items as $file) {
                $total += (int) ($file['size'] ?? 0);
            }
        }

        return $total;
    }

    /**
     * Удалить все временные файлы (с диска и из сессии).
     */
    public static function clearTemp(): void
    {
        foreach (self::tempFiles() as $collection => $entry) {
            $items = $collection === 'other_documents' ? $entry : [$entry];
            foreach ($items as $file) {
                if (! empty($file['path'])) {
                    Storage::disk('local')->delete($file['path']);
                }
            }
        }

        session()->forget(self::TEMP_SESSION_KEY);
    }
}
