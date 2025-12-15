<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRfqRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Только модераторы компаний могут создавать RFQ
        return $this->user() && $this->user()->isModeratorOfAnyCompany();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company_id' => 'required|exists:companies,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:open,closed',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'weight_price' => 'required|numeric|min:0|max:100',
            'weight_deadline' => 'required|numeric|min:0|max:100',
            'weight_advance' => 'required|numeric|min:0|max:100',
            'technical_specification' => 'required|file|mimes:pdf|max:10240', // 10MB
            'invited_companies' => 'nullable|array',
            'invited_companies.*' => 'exists:companies,id',
        ];
    }

    /**
     * Валидация после основных правил
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Проверка: сумма весов = 100%
            $totalWeight = $this->weight_price + $this->weight_deadline + $this->weight_advance;
            if (abs($totalWeight - 100) > 0.01) {
                $validator->errors()->add('weights', 'Сумма весов критериев должна быть равна 100%');
            }

            // Проверка: пользователь является модератором выбранной компании
            if ($this->company_id && !$this->user()->isModeratorOf(\App\Models\Company::find($this->company_id))) {
                $validator->errors()->add('company_id', 'Вы не являетесь модератором этой компании');
            }
        });
    }

    /**
     * Кастомные сообщения об ошибках
     */
    public function messages(): array
    {
        return [
            'company_id.required' => 'Выберите компанию-организатора',
            'title.required' => 'Укажите название запроса котировок',
            'start_date.after_or_equal' => 'Дата начала не может быть в прошлом',
            'end_date.after' => 'Дата окончания должна быть позже даты начала',
            'technical_specification.required' => 'Загрузите техническое задание (PDF)',
        ];
    }
}