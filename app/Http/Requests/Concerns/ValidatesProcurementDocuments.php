<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

use App\Support\ProcurementDocuments;
use Illuminate\Contracts\Validation\Validator;

/**
 * #185 Общие правила валидации конкурсной документации для Form Request'ов
 * RFQ / Аукциона (Store + Update). Все файлы — PDF; суммарный объём ≤ 20 МБ.
 */
trait ValidatesProcurementDocuments
{
    /**
     * @return array<string, array<int, string>>
     */
    protected function procurementDocumentRules(): array
    {
        return [
            'notice' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
            'technical_specification' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
            'contract_draft' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
            'other_documents' => ['nullable', 'array'],
            'other_documents.*' => ['file', 'mimes:pdf', 'max:20480'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function procurementDocumentMessages(): array
    {
        return [
            'notice.mimes' => 'Извещение должно быть в формате PDF.',
            'notice.max' => 'Извещение не должно превышать 20 МБ.',
            'technical_specification.mimes' => 'Техническое задание должно быть в формате PDF.',
            'technical_specification.max' => 'Техническое задание не должно превышать 20 МБ.',
            'contract_draft.mimes' => 'Проект договора должен быть в формате PDF.',
            'contract_draft.max' => 'Проект договора не должен превышать 20 МБ.',
            'other_documents.*.mimes' => 'Прочие файлы должны быть в формате PDF.',
            'other_documents.*.max' => 'Каждый файл не должен превышать 20 МБ.',
        ];
    }

    /**
     * Проверка суммарного объёма загружаемой документации (≤ 20 МБ).
     */
    protected function validateProcurementDocumentsTotalSize(Validator $validator): void
    {
        $total = 0;

        foreach (ProcurementDocuments::SINGLE_COLLECTIONS as $field) {
            if ($this->hasFile($field)) {
                $total += (int) $this->file($field)->getSize();
            }
        }

        if ($this->hasFile('other_documents')) {
            foreach ((array) $this->file('other_documents') as $file) {
                $total += (int) $file->getSize();
            }
        }

        if ($total > ProcurementDocuments::MAX_TOTAL_BYTES) {
            $validator->errors()->add(
                'other_documents',
                'Общий объём конкурсной документации не должен превышать 20 МБ.'
            );
        }
    }
}
