<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * #179 Валидация предложения в коммерческом аукционе (этап 2).
 */
class StoreCommercialOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company_id' => 'required|exists:companies,id',
            'price' => 'required|numeric|gt:0',
            'deadline' => 'required|integer|min:1',
            'advance_percent' => 'required|numeric|min:0|max:100',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'company_id.required' => 'Выберите компанию-участника',
            'price.required' => 'Укажите цену',
            'price.gt' => 'Цена должна быть больше нуля',
            'deadline.required' => 'Укажите срок выполнения',
            'deadline.min' => 'Срок должен быть не менее 1 дня',
            'advance_percent.required' => 'Укажите размер аванса',
            'advance_percent.max' => 'Аванс не может превышать 100%',
        ];
    }
}
