<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRfqRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $rfq = $this->route('rfq');
        return $rfq && $rfq->canManage($this->user());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'end_date' => ['sometimes', 'required', 'date', 'after:' . $this->route('rfq')->start_date],
            'technical_specification' => 'nullable|file|mimes:pdf|max:10240',
            'technical_specification_temp' => 'nullable|string',
        ];
    }

    /**
     * Кастомные сообщения об ошибках
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Укажите название запроса котировок',
            'end_date.after' => 'Дата окончания должна быть позже даты начала',
        ];
    }
}