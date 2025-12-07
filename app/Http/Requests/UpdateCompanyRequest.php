<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $company = $this->route('company');
        
        // Только создатель или модератор компании может её редактировать
        return auth()->check() && (
            $company->created_by === auth()->id() ||
            $company->isModerator(auth()->user())
        );
    }

    public function rules(): array
    {
        $companyId = $this->route('company')->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'inn' => ['sometimes', 'required', 'string', 'size:12', Rule::unique('companies')->ignore($companyId)],
            'legal_form' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'full_description' => ['nullable', 'string'],
            'industry_id' => ['nullable', 'exists:industries,id'],
            'documents.*' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Название компании обязательно для заполнения',
            'inn.required' => 'ИНН обязателен для заполнения',
            'inn.size' => 'ИНН должен содержать 12 цифр',
            'inn.unique' => 'Компания с таким ИНН уже зарегистрирована',
            'logo.image' => 'Логотип должен быть изображением',
            'logo.max' => 'Размер логотипа не должен превышать 2MB',
            'industry_id.exists' => 'Выбранная отрасль не существует',
            'documents.*.mimes' => 'Документы должны быть в формате PDF',
            'documents.*.max' => 'Размер документа не должен превышать 10MB',
        ];
    }
}