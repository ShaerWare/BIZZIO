<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesCompanyDocuments;
use App\Rules\CompanyNameWithoutLegalForm;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyRequest extends FormRequest
{
    use ValidatesCompanyDocuments;

    public function authorize(): bool
    {
        // #187 Профиль редактируют создатель и участники с ролью owner/admin/moderator.
        // Рядовой «Участник» (member) — не может, хотя и числится в company_user.
        return $this->route('company')->canEditProfile($this->user());
    }

    public function rules(): array
    {
        $company = $this->route('company');
        $companyId = $company->id;

        // #287 Название без ОПФ и кавычек требуем только когда его меняют: у компаний,
        // заведённых раньше, ОПФ может быть уже вписана в название, и правило заблокировало бы
        // им сохранение любых других полей.
        $nameRules = ['sometimes', 'required', 'string', 'max:255'];
        if ($this->has('name') && trim((string) $this->input('name')) !== trim((string) $company->name)) {
            $nameRules[] = new CompanyNameWithoutLegalForm;
        }

        return array_merge([
            'name' => $nameRules,
            'inn' => ['sometimes', 'required', 'string', 'regex:/^\d{10}(\d{2})?$/', Rule::unique('companies', 'inn')->ignore($companyId)->whereNull('deleted_at')],
            'legal_form' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'full_description' => ['nullable', 'string'],
            'industry_id' => ['nullable', 'exists:industries,id'],
        ], $this->companyDocumentRules());
    }

    /**
     * #176 Суммарный объём документов компании (с учётом уже загруженных) — не более 10 МБ.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateCompanyDocumentsTotalSize($validator, $this->route('company'));
        });
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Название компании обязательно для заполнения',
            'inn.required' => 'ИНН обязателен для заполнения',
            'inn.regex' => 'ИНН должен содержать 10 цифр (для ИП/юрлица) или 12 цифр (для физлица)',
            'inn.unique' => 'Компания с таким ИНН уже зарегистрирована',
            'logo.image' => 'Логотип должен быть изображением',
            'logo.max' => 'Размер логотипа не должен превышать 2MB',
            'industry_id.exists' => 'Выбранная отрасль не существует',
            ...$this->companyDocumentMessages(),
        ];
    }
}
