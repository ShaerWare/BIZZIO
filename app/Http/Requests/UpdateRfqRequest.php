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

        // #216 Единое правило с RfqPolicy::update — редактируется только черновик.
        return $rfq && $this->user()?->can('update', $rfq);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rfq = $this->route('rfq');

        // #216 Черновик редактируется полностью (после активации редактирование запрещено политикой).
        $rules = [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'is_results_hidden' => 'nullable|boolean',
            'type' => 'sometimes|required|in:open,closed',
            'currency' => 'sometimes|required|in:RUB,USD,CNY',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after:start_date',
            'weight_price' => 'sometimes|required|numeric|min:0|max:100',
            'weight_deadline' => 'sometimes|required|numeric|min:0|max:100',
            'weight_advance' => 'sometimes|required|numeric|min:0|max:100',
        ];

        // #179/#210 Параметры этапа 2 — только для коммерческого аукциона.
        if ($rfq->isCommercial()) {
            $rules += [
                'trading_start' => 'sometimes|required|date|after:end_date',
                'step_price' => 'sometimes|required|numeric|min:0.01|max:100',
                'step_deadline' => 'sometimes|required|integer|min:1',
                'step_advance' => 'sometimes|required|numeric|min:0.01|max:100',
                'max_deadline' => 'sometimes|required|integer|min:1',
                'max_advance' => 'sometimes|required|numeric|min:0.01|max:100',
            ];
        }

        // #185 Конкурсная документация (Извещение / ТЗ / Проект договора / Прочие файлы).
        return array_merge($rules, $this->procurementDocumentRules());
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

            // #216 Сумма весов критериев = 100%.
            if ($this->has('weight_price')) {
                $total = (float) $this->weight_price + (float) $this->weight_deadline + (float) $this->weight_advance;
                if (abs($total - 100) > 0.01) {
                    $validator->errors()->add('weights', 'Сумма весов критериев должна быть равна 100%');
                }
            }
        });
    }

    public function messages(): array
    {
        return array_merge([
            'title.required' => 'Укажите название запроса цен',
            'end_date.after' => 'Дата окончания должна быть позже даты начала',
            // #216
            'start_date.required' => 'Укажите дату начала приёма заявок',
            'type.required' => 'Выберите тип процедуры',
            'currency.required' => 'Выберите валюту',
            'trading_start.required' => 'Укажите дату начала коммерческого аукциона (этап 2)',
            'trading_start.after' => 'Начало торгов должно быть позже окончания приёма предложений',
            'step_price.required' => 'Укажите шаг изменения цены (%)',
            'step_deadline.required' => 'Укажите шаг изменения срока (дни)',
            'step_advance.required' => 'Укажите шаг изменения аванса (%)',
            'max_deadline.required' => 'Укажите максимальный срок выполнения (дни)',
            'max_advance.required' => 'Укажите максимальный размер аванса (%)',
        ], $this->procurementDocumentMessages());
    }
}
