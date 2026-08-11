<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

use App\Models\Company;
use Illuminate\Contracts\Validation\Validator;

/**
 * #176 Общие правила для документов компании: только PDF, не более 10 файлов,
 * суммарный объём — не более 10 МБ с учётом уже загруженных ранее.
 */
trait ValidatesCompanyDocuments
{
    /** Суммарный лимит объёма документов компании (байты) — 10 МБ. */
    public const DOCUMENTS_MAX_TOTAL_BYTES = 10 * 1024 * 1024;

    /** Максимальное число документов компании. */
    public const DOCUMENTS_MAX_COUNT = 10;

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function companyDocumentRules(): array
    {
        return [
            'documents' => ['nullable', 'array', 'max:'.self::DOCUMENTS_MAX_COUNT],
            'documents.*' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function companyDocumentMessages(): array
    {
        return [
            'documents.max' => 'За один раз можно загрузить не более '.self::DOCUMENTS_MAX_COUNT.' файлов.',
            'documents.*.mimes' => 'Документы должны быть в формате PDF',
            'documents.*.max' => 'Размер документа не должен превышать 10MB',
        ];
    }

    /**
     * Суммарный объём: новые файлы + уже загруженные (при редактировании).
     * Лишние документы пользователь может удалить по одному прямо в форме.
     */
    protected function validateCompanyDocumentsTotalSize(Validator $validator, ?Company $company = null): void
    {
        if (! $this->hasFile('documents')) {
            return;
        }

        $total = 0;

        foreach ((array) $this->file('documents') as $file) {
            $total += (int) $file->getSize();
        }

        $existing = $company ? $company->getMedia('documents') : collect();
        $total += (int) $existing->sum('size');

        if ($total > self::DOCUMENTS_MAX_TOTAL_BYTES) {
            $validator->errors()->add(
                'documents',
                'Общий объём документов компании не должен превышать 10 МБ. Удалите лишние файлы и повторите загрузку.'
            );
        }

        if ($existing->count() + count((array) $this->file('documents')) > self::DOCUMENTS_MAX_COUNT) {
            $validator->errors()->add(
                'documents',
                'У компании может быть не более '.self::DOCUMENTS_MAX_COUNT.' документов.'
            );
        }
    }
}
