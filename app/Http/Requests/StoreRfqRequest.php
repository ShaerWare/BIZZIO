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
            'currency' => 'required|in:RUB,USD,CNY',
            'status' => 'required|in:draft,active',
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
            
            // Проверка статуса (дополнительная защита)
            if ($this->status && !in_array($this->status, ['draft', 'active'])) {
                $validator->errors()->add('status', 'Недопустимое значение статуса');
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
            'company_id.exists' => 'Выбранная компания не найдена',
            'title.required' => 'Укажите название запроса котировок',
            'title.max' => 'Название не должно превышать 255 символов',
            'type.required' => 'Выберите тип процедуры',
            'type.in' => 'Недопустимый тип процедуры (выберите: открытая или закрытая)',
            'status.required' => 'Выберите статус RFQ',
            'status.in' => 'Недопустимый статус (выберите: черновик или активный)',
            'start_date.required' => 'Укажите дату начала приёма заявок',
            'start_date.date' => 'Неверный формат даты начала',
            'start_date.after_or_equal' => 'Дата начала не может быть в прошлом',
            'end_date.required' => 'Укажите дату окончания приёма заявок',
            'end_date.date' => 'Неверный формат даты окончания',
            'end_date.after' => 'Дата окончания должна быть позже даты начала',
            'weight_price.required' => 'Укажите вес критерия "Цена"',
            'weight_price.numeric' => 'Вес "Цена" должен быть числом',
            'weight_price.min' => 'Вес "Цена" не может быть отрицательным',
            'weight_price.max' => 'Вес "Цена" не может превышать 100',
            'weight_deadline.required' => 'Укажите вес критерия "Срок выполнения"',
            'weight_deadline.numeric' => 'Вес "Срок выполнения" должен быть числом',
            'weight_deadline.min' => 'Вес "Срок выполнения" не может быть отрицательным',
            'weight_deadline.max' => 'Вес "Срок выполнения" не может превышать 100',
            'weight_advance.required' => 'Укажите вес критерия "Размер аванса"',
            'weight_advance.numeric' => 'Вес "Размер аванса" должен быть числом',
            'weight_advance.min' => 'Вес "Размер аванса" не может быть отрицательным',
            'weight_advance.max' => 'Вес "Размер аванса" не может превышать 100',
            'technical_specification.required' => 'Загрузите техническое задание (PDF)',
            'technical_specification.file' => 'Техническое задание должно быть файлом',
            'technical_specification.mimes' => 'Техническое задание должно быть в формате PDF',
            'technical_specification.max' => 'Размер файла не должен превышать 10 МБ',
            'invited_companies.array' => 'Неверный формат списка приглашённых компаний',
            'invited_companies.*.exists' => 'Одна из приглашённых компаний не найдена',
        ];
    }

    /**
     * Кастомные названия атрибутов для ошибок
     */
    public function attributes(): array
    {
        return [
            'company_id' => 'компания-организатор',
            'title' => 'название',
            'description' => 'описание',
            'type' => 'тип процедуры',
            'status' => 'статус',
            'start_date' => 'дата начала',
            'end_date' => 'дата окончания',
            'weight_price' => 'вес "Цена"',
            'weight_deadline' => 'вес "Срок выполнения"',
            'weight_advance' => 'вес "Размер аванса"',
            'technical_specification' => 'техническое задание',
            'invited_companies' => 'приглашённые компании',
        ];
    }
}