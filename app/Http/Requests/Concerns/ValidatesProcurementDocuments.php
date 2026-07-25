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
     * #185 Загружен ли документ в коллекцию — в запросе или во временном хранилище
     * (файл, сохранённый при предыдущей ошибке валидации).
     */
    protected function procurementDocumentUploaded(string $collection): bool
    {
        if ($collection === 'other_documents') {
            return $this->hasFile('other_documents') || ProcurementDocuments::hasTemp('other_documents');
        }

        return $this->hasFile($collection) || ProcurementDocuments::hasTemp($collection);
    }

    /**
     * Проверка суммарного объёма загружаемой документации (≤ 20 МБ).
     * Учитывает как файлы из запроса, так и сохранённые во временном хранилище.
     */
    protected function validateProcurementDocumentsTotalSize(Validator $validator): void
    {
        // #185 Временные файлы (сохранённые при ошибке валидации).
        $total = ProcurementDocuments::tempTotalSize();

        foreach (ProcurementDocuments::SINGLE_COLLECTIONS as $field) {
            // Файл из запроса имеет приоритет над temp — считаем его только если temp по этой коллекции нет.
            if ($this->hasFile($field) && ! ProcurementDocuments::hasTemp($field)) {
                $total += (int) $this->file($field)->getSize();
            }
        }

        // Файлы из запроса считаем только если temp по «прочим» пуст (иначе двойной учёт).
        if ($this->hasFile('other_documents') && ! ProcurementDocuments::hasTemp('other_documents')) {
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
