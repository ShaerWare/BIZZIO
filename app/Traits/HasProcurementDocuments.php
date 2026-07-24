<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Support\Collection;

/**
 * #185 Конкурсная документация процедуры (Запрос цен / Аукцион / Коммерческий аукцион).
 *
 * Четыре типа документов под общим заголовком «Конкурсная документация»:
 *   notice                — Извещение (1 файл)
 *   technical_specification — Техническое задание (ТЗ) (1 файл)
 *   contract_draft        — Проект договора (1 файл)
 *   other_documents       — Прочие файлы (несколько)
 *
 * Все — только PDF. Общий лимит объёма (20 МБ) проверяется в Form Request.
 */
trait HasProcurementDocuments
{
    /** Коллекции документов: ключ = media-коллекция, значение = человекочитаемая метка. */
    public const DOCUMENT_COLLECTIONS = [
        'notice' => 'Извещение',
        'technical_specification' => 'Техническое задание (ТЗ)',
        'contract_draft' => 'Проект договора',
        'other_documents' => 'Прочие файлы',
    ];

    /** Одиночные коллекции (ровно один файл). */
    public const SINGLE_DOCUMENT_COLLECTIONS = ['notice', 'technical_specification', 'contract_draft'];

    /** Общий лимит объёма конкурсной документации (байты) — 20 МБ. */
    public const DOCUMENTS_MAX_TOTAL_BYTES = 20 * 1024 * 1024;

    /**
     * Регистрация media-коллекций конкурсной документации (PDF).
     * Вызывается из registerMediaCollections() модели.
     */
    public function registerProcurementDocumentMediaCollections(): void
    {
        foreach (self::SINGLE_DOCUMENT_COLLECTIONS as $collection) {
            $this->addMediaCollection($collection)
                ->singleFile()
                ->acceptsMimeTypes(['application/pdf']);
        }

        // Прочие файлы — несколько PDF.
        $this->addMediaCollection('other_documents')
            ->acceptsMimeTypes(['application/pdf']);
    }

    /**
     * Все файлы конкурсной документации (по всем коллекциям), в порядке типов.
     *
     * @return Collection<int, \Spatie\MediaLibrary\MediaCollections\Models\Media>
     */
    public function allProcurementDocuments(): Collection
    {
        return collect(array_keys(self::DOCUMENT_COLLECTIONS))
            ->flatMap(fn (string $collection) => $this->getMedia($collection))
            ->values();
    }

    public function hasAnyProcurementDocument(): bool
    {
        return $this->allProcurementDocuments()->isNotEmpty();
    }
}
