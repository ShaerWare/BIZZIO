<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesProcurementDocuments;
use Illuminate\Foundation\Http\FormRequest;

class StoreRfqRequest extends FormRequest
{
    use ValidatesProcurementDocuments;

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
        return array_merge([
            'company_id' => 'required|exists:companies,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:open,closed',
            'procedure' => 'nullable|in:standard,commercial',
            'currency' => 'required|in:RUB,USD,CNY',
            'status' => 'required|in:draft,active',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'weight_price' => 'required|numeric|min:0|max:100',
            'weight_deadline' => 'required|numeric|min:0|max:100',
            'weight_advance' => 'required|numeric|min:0|max:100',
            'invited_companies' => 'nullable|array',
            'invited_companies.*' => 'exists:companies,id',
            'is_results_hidden' => 'nullable|boolean',

            // #179 Параметры этапа 2 (Коммерческий аукцион) — обязательны при procedure=commercial.
            // trading_end не задаётся: торги закрываются через 20 мин после последнего предложения.
            // #210 max_deadline/max_advance задаёт организатор — референсы нормировки (100% шкалы) этапа 2.
            'trading_start' => 'nullable|required_if:procedure,commercial|date|after:end_date',
            'step_price' => 'nullable|required_if:procedure,commercial|numeric|min:0.01|max:100',
            'step_deadline' => 'nullable|required_if:procedure,commercial|integer|min:1',
            'step_advance' => 'nullable|required_if:procedure,commercial|numeric|min:0.01|max:100',
            'max_deadline' => 'nullable|required_if:procedure,commercial|integer|min:1',
            'max_advance' => 'nullable|required_if:procedure,commercial|numeric|min:0.01|max:100',
            // #185 Конкурсная документация (Извещение / ТЗ / Проект договора / Прочие файлы).
        ], $this->procurementDocumentRules());
    }

    /**
     * Валидация после основных правил
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // #185 Техническое задание обязательно при создании (учитываем temp-файл).
            if (! $this->procurementDocumentUploaded('technical_specification')) {
                $validator->errors()->add('technical_specification', 'Загрузите техническое задание (PDF)');
            }

            // #185 Общий объём конкурсной документации ≤ 20 МБ.
            $this->validateProcurementDocumentsTotalSize($validator);

            // Проверка: сумма весов = 100%
            $totalWeight = $this->weight_price + $this->weight_deadline + $this->weight_advance;
            if (abs($totalWeight - 100) > 0.01) {
                $validator->errors()->add('weights', 'Сумма весов критериев должна быть равна 100%');
            }

            // Проверка: пользователь является модератором выбранной компании
            if ($this->company_id && ! $this->user()->isModeratorOf(\App\Models\Company::find($this->company_id))) {
                $validator->errors()->add('company_id', 'Вы не являетесь модератором этой компании');
            }

            // Проверка статуса (дополнительная защита)
            if ($this->status && ! in_array($this->status, ['draft', 'active'])) {
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
            'title.required' => 'Укажите название запроса цен',
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
            'invited_companies.array' => 'Неверный формат списка приглашённых компаний',
            'invited_companies.*.exists' => 'Одна из приглашённых компаний не найдена',

            // #179
            'trading_start.required_if' => 'Укажите дату начала коммерческого аукциона (этап 2)',
            'trading_start.after' => 'Начало торгов должно быть позже окончания приёма предложений',
            'step_price.required_if' => 'Укажите шаг изменения цены (%)',
            'step_deadline.required_if' => 'Укажите шаг изменения срока (дни)',
            'step_advance.required_if' => 'Укажите шаг изменения аванса (%)',
            // #210
            'max_deadline.required_if' => 'Укажите максимальный срок выполнения (дни)',
            'max_advance.required_if' => 'Укажите максимальный размер аванса (%)',
            // #185 Сообщения по конкурсной документации.
            ...$this->procurementDocumentMessages(),
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
