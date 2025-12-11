<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Только авторизованные пользователи могут создавать проекты
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'full_description' => 'nullable|string',
            'avatar' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048', // 2MB
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_ongoing' => 'boolean',
            'status' => 'required|in:active,completed,cancelled',
            'company_id' => 'required|exists:companies,id',
            
            // Участники проекта (массив компаний с их ролями)
            'participants' => 'nullable|array',
            'participants.*.company_id' => 'required|exists:companies,id',
            'participants.*.role' => 'required|in:customer,general_contractor,contractor,supplier,consultant',
            'participants.*.participation_description' => 'nullable|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Название проекта обязательно для заполнения.',
            'name.max' => 'Название проекта не должно превышать 255 символов.',
            'avatar.image' => 'Файл должен быть изображением.',
            'avatar.mimes' => 'Допустимые форматы: JPEG, JPG, PNG, WebP.',
            'avatar.max' => 'Размер изображения не должен превышать 2MB.',
            'start_date.required' => 'Дата начала проекта обязательна.',
            'end_date.after_or_equal' => 'Дата окончания должна быть не раньше даты начала.',
            'company_id.required' => 'Компания-заказчик обязательна.',
            'company_id.exists' => 'Выбранная компания не существует.',
            'participants.*.company_id.exists' => 'Одна из выбранных компаний-участников не существует.',
            'participants.*.role.in' => 'Недопустимая роль участника.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Если чекбокс is_ongoing не отправлен, устанавливаем false
        if (!$this->has('is_ongoing')) {
            $this->merge(['is_ongoing' => false]);
        }
        
        // Если проект продолжается, убираем end_date
        if ($this->is_ongoing) {
            $this->merge(['end_date' => null]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Проверка прав: пользователь должен быть модератором выбранной компании
            $companyId = $this->input('company_id');
            $user = auth()->user();
            
            if ($companyId) {
                $company = \App\Models\Company::find($companyId);
                
                // ИСПРАВЛЕНО: hasRole() → inRole()
                if ($company && !$company->isModerator($user) && !$user->inRole('admin')) {
                    $validator->errors()->add(
                        'company_id',
                        'Вы не являетесь модератором этой компании и не можете создать проект от её имени.'
                    );
                }
            }
        });
    }
}