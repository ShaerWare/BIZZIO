<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAuctionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Только модераторы компаний могут создавать аукционы
        $companyId = $this->input('company_id');
        
        if (!$companyId) {
            return false;
        }
        
        $company = \App\Models\Company::find($companyId);
        
        return $company && $company->isModerator(auth()->user());
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'company_id' => ['required', 'exists:companies,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', Rule::in(['open', 'closed'])],
            'start_date' => ['required', 'date', 'after:now'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'trading_start' => ['required', 'date', 'after:end_date'],
            'starting_price' => ['required', 'numeric', 'min:1'],
            'status' => ['required', Rule::in(['draft', 'active'])],
            'technical_specification' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'invited_companies' => ['nullable', 'array'],
            'invited_companies.*' => ['exists:companies,id'],
            'notification_agreement' => ['required', 'accepted'],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'company_id.required' => 'Необходимо выбрать компанию-организатора.',
            'company_id.exists' => 'Выбранная компания не существует.',
            'title.required' => 'Название аукциона обязательно.',
            'type.required' => 'Необходимо выбрать тип процедуры.',
            'start_date.required' => 'Укажите дату начала приёма заявок.',
            'start_date.after' => 'Дата начала должна быть в будущем.',
            'end_date.required' => 'Укажите дату окончания приёма заявок.',
            'end_date.after' => 'Дата окончания должна быть позже даты начала.',
            'trading_start.required' => 'Укажите дату начала торгов.',
            'trading_start.after' => 'Дата начала торгов должна быть после окончания приёма заявок.',
            'starting_price.required' => 'Укажите начальную (максимальную) цену.',
            'starting_price.min' => 'Начальная цена должна быть больше нуля.',
            'technical_specification.mimes' => 'Техническое задание должно быть в формате PDF.',
            'technical_specification.max' => 'Размер файла не должен превышать 10 МБ.',
            'notification_agreement.accepted' => 'Необходимо подтвердить согласие с условиями.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Если тип "открытая", убираем приглашения
        if ($this->type === 'open') {
            $this->merge(['invited_companies' => []]);
        }
    }
}