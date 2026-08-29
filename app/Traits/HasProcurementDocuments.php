<?php

declare(strict_types=1);

namespace App\Traits;

use App\Support\ProcurementDocuments;
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
 * Все — только PDF. Константы — в App\Support\ProcurementDocuments.
 */
trait HasProcurementDocuments
{
    /**
     * Регистрация media-коллекций конкурсной документации (PDF).
     * Вызывается из registerMediaCollections() модели.
     */
    public function registerProcurementDocumentMediaCollections(): void
    {
        foreach (ProcurementDocuments::SINGLE_COLLECTIONS as $collection) {
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
        return collect(array_keys(ProcurementDocuments::COLLECTIONS))
            ->flatMap(fn (string $collection) => $this->getMedia($collection))
            ->values();
    }

    /**
     * #296 Есть ли документация у процедуры (у этапа 2 — документация этапа 1).
     */
    public function hasAnyProcurementDocument(): bool
    {
        return $this->procurementDocumentsHolder()->allProcurementDocuments()->isNotEmpty();
    }

    /**
     * #296 Процедура, на которой физически лежат файлы документации.
     *
     * По умолчанию — сама процедура. У коммерческого аукциона (этап 2) своих файлов нет:
     * документация загружается организатором на этапе 1, поэтому носитель — связанный
     * запрос цен (переопределено в App\Models\Auction).
     *
     * @return \Illuminate\Database\Eloquent\Model&self
     */
    public function procurementDocumentsHolder(): \Illuminate\Database\Eloquent\Model
    {
        return $this;
    }

    /**
     * #296 Момент, от которого отсчитывается срок хранения документации.
     *
     * Для двухэтапной процедуры это завершение этапа 2: пока идут торги, документация
     * этапа 1 должна оставаться доступной (переопределено в App\Models\Rfq).
     * null — срок ещё не начался.
     */
    public function documentsRetentionStartedAt(): ?\Illuminate\Support\Carbon
    {
        if (! in_array($this->status, ['closed', 'cancelled'], true)) {
            return null;
        }

        // closed_at заполнен у всех завершённых процедур (миграция #296);
        // updated_at остаётся запасным вариантом для старых записей.
        return $this->closed_at ?? $this->updated_at;
    }

    /**
     * #296 Дата, до которой документация доступна после завершения процедуры.
     *
     * Срок хранения (по умолчанию 30 дней) отсчитывается от завершения этапа 2.
     * Пока процедура не завершена, срок не начинается и метод возвращает null.
     */
    public function documentsAvailableUntil(): ?\Illuminate\Support\Carbon
    {
        $startedAt = $this->documentsRetentionStartedAt();

        if ($startedAt === null) {
            return null;
        }

        return $startedAt->copy()->addDays(\App\Models\Setting::documentsRetentionDays());
    }

    /**
     * #296 Истёк ли срок хранения документации (файлы удаляются командой `documents:cleanup`).
     */
    public function documentsRetentionExpired(): bool
    {
        $until = $this->documentsAvailableUntil();

        return $until !== null && $until->isPast();
    }

    /**
     * #185 Доступ к конкурсной документации.
     *
     * Пока процедура активна (не завершена/не отменена) — документы доступны всем
     * (участникам для подготовки заявок). После завершения доступ закрывается:
     * остаётся только у организатора и компаний-участников (подавших заявку или приглашённых).
     *
     * #296 И только в течение срока хранения (по умолчанию 30 дней после завершения этапа 2):
     * дальше файлы удаляются, поэтому доступ закрыт и для организатора.
     */
    public function documentsAccessibleBy(?\App\Models\User $user): bool
    {
        if (! in_array($this->status, ['closed', 'cancelled'], true)) {
            return true;
        }

        if ($this->documentsRetentionExpired()) {
            return false;
        }

        if (! $user) {
            return false;
        }

        if (method_exists($this, 'canManage') && $this->canManage($user)) {
            return true;
        }

        $companyIds = $user->moderatedCompanies()->pluck('companies.id');

        if ($companyIds->isEmpty()) {
            return false;
        }

        return $this->bids()->whereIn('company_id', $companyIds)->exists()
            || $this->invitations()->whereIn('company_id', $companyIds)->exists();
    }
}
