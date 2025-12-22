<?php

namespace App\Http\Requests;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyOrchidRequest extends FormRequest
{
    protected ?Company $companyModel = null;

    public function authorize(): bool
    {
        // Получаем ID компании из формы
        $companyId = $this->input('company.id');

        if (!$companyId) {
            // Если создание новой компании — разрешено всем авторизованным
            return auth()->check();
        }

        // Загружаем модель один раз
        $this->companyModel = Company::findOrFail($companyId);

        return auth()->check() && (
            $this->companyModel->created_by === auth()->id() ||
            $this->companyModel->isModerator(auth()->user())
        );
    }

    public function rules(): array
    {
        $companyId = $this->input('company.id');

        return [
            'company.name'              => ['sometimes', 'required', 'string', 'max:255'],
            'company.inn'               => [
                'sometimes',
                'required',
                'string',
                'size:10',
                'regex:/^\d{10}$/',
                Rule::unique('companies', 'inn')->ignore($companyId),
            ],
            'company.legal_form'        => ['nullable', 'string', 'max:255'],
            'company.short_description' => ['nullable', 'string', 'max:500'],
            'company.full_description'  => ['nullable', 'string'],
            'company.industry_id'       => ['nullable', 'exists:industries,id'],
            // Логотип — только если пришёл файл
            'company.logo'              => ['nullable', 'array'],
            'company.logo.*'            => [
                'nullable',
                'image',
                'mimes:jpeg,png,gif,webp',
                'max:2048',
            ],
            'company.documents'         => ['nullable', 'array'],
            'company.documents.*'       => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'company.moderators'        => ['nullable', 'array'],
            'company.moderators.*'      => ['exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'company.name.required'             => 'Название компании обязательно',
            'company.inn.required'              => 'ИНН обязателен',
            'company.inn.size'                  => 'ИНН должен содержать ровно 10 цифр',
            'company.inn.regex'                 => 'ИНН должен состоять только из 10 цифр',
            'company.inn.unique'                => 'Компания с таким ИНН уже существует',
            'company.logo.*.image'              => 'Логотип должен быть изображением',
        ];
    }

    // Опционально: доступ к модели из контроллера/экрана
    public function getCompany(): ?Company
    {
        return $this->companyModel;
    }
}