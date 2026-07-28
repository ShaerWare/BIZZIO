<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesProcurementDocuments;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRfqRequest extends FormRequest
{
    use ValidatesProcurementDocuments;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $rfq = $this->route('rfq');

        return $rfq && $rfq->canManage($this->user());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'end_date' => ['sometimes', 'required', 'date', 'after:'.$this->route('rfq')->start_date],
            'is_results_hidden' => 'nullable|boolean',
            // #185 Конкурсная документация (Извещение / ТЗ / Проект договора / Прочие файлы).
        ], $this->procurementDocumentRules());
    }

    /**
     * Кастомные сообщения об ошибках
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_results_hidden' => $this->boolean('is_results_hidden'),
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // #185 Общий объём конкурсной документации ≤ 20 МБ.
            $this->validateProcurementDocumentsTotalSize($validator);
        });
    }

    public function messages(): array
    {
        return array_merge([
            'title.required' => 'Укажите название запроса цен',
            'end_date.after' => 'Дата окончания должна быть позже даты начала',
        ], $this->procurementDocumentMessages());
    }
}
