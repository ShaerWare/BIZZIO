<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Только авторизованные пользователи могут создавать компании
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'inn' => ['required', 'string', 'regex:/^\d{10}(\d{2})?$/', 'unique:companies,inn'],
            'legal_form' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'], // max 2MB
            'short_description' => ['nullable', 'string', 'max:500'],
            'full_description' => ['nullable', 'string'],
            'industry_id' => ['nullable', 'exists:industries,id'],
            'documents.*' => ['nullable', 'file', 'mimes:pdf', 'max:10240'], // max 10MB
        ];
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
            'documents.*.mimes' => 'Документы должны быть в формате PDF',
            'documents.*.max' => 'Размер документа не должен превышать 10MB',
        ];
    }
}