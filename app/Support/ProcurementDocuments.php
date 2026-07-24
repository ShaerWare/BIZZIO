<?php

declare(strict_types=1);

namespace App\Support;

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
}
