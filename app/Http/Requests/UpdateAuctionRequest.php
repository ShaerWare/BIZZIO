<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesProcurementDocuments;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAuctionRequest extends FormRequest
{
    use ValidatesProcurementDocuments;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $auction = $this->route('auction');

        // Только создатель или модератор компании может редактировать
        // И только если статус = 'draft'
        return $auction->status === 'draft' && $auction->canManage(auth()->user());
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'trading_start' => ['required', 'date', 'after:end_date'],
            'currency' => ['required', 'string', Rule::in(array_keys(\App\Models\Auction::CURRENCIES))],
            'starting_price' => ['required', 'numeric', 'min:1'],
            // #196 Организатор задаёт минимальный шаг снижения цены (0.5%–5%).
            'step_percent' => ['required', 'numeric', 'min:0.5', 'max:5'],
            'is_results_hidden' => ['nullable', 'boolean'],
            // #185 Конкурсная документация (Извещение / ТЗ / Проект договора / Прочие файлы).
        ] + $this->procurementDocumentRules();
    }

    /**
     * Get custom validation messages.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_results_hidden' => $this->boolean('is_results_hidden'),
        ]);
    }

    /**
     * #185 Общий объём конкурсной документации ≤ 20 МБ.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateProcurementDocumentsTotalSize($validator);
        });
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Название аукциона обязательно.',
            'end_date.required' => 'Укажите дату окончания приёма заявок.',
            'end_date.after' => 'Дата окончания должна быть позже даты начала.',
            'trading_start.required' => 'Укажите дату начала торгов.',
            'trading_start.after' => 'Дата начала торгов должна быть после окончания приёма заявок.',
            'starting_price.required' => 'Укажите начальную (максимальную) цену.',
            'starting_price.min' => 'Начальная цена должна быть больше нуля.',
            'step_percent.required' => 'Укажите минимальный шаг снижения цены.',
            'step_percent.min' => 'Шаг аукциона должен быть не менее 0.5%.',
            'step_percent.max' => 'Шаг аукциона должен быть не более 5%.',
        ] + $this->procurementDocumentMessages();
    }
}
