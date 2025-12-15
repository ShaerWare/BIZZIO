<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBidRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $rfq = $this->route('rfq');
        
        // Проверка: RFQ активен
        if (!$rfq || !$rfq->isActive()) {
            return false;
        }

        // Проверка: пользователь является модератором какой-либо компании
        if (!$this->user() || !$this->user()->isModeratorOfAnyCompany()) {
            return false;
        }

        // Проверка: для закрытых процедур нужно приглашение
        if ($rfq->type === 'closed') {
            return $rfq->invitations()
                ->where('company_id', $this->company_id)
                ->exists();
        }

        return true;
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
            'price' => 'required|numeric|min:0',
            'deadline' => 'required|integer|min:1',
            'advance_percent' => 'required|numeric|min:0|max:100',
            'comment' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Валидация после основных правил
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Проверка: пользователь является модератором выбранной компании
            if ($this->company_id && !$this->user()->isModeratorOf(\App\Models\Company::find($this->company_id))) {
                $validator->errors()->add('company_id', 'Вы не являетесь модератором этой компании');
            }

            // Проверка: компания ещё не подавала заявку
            $rfq = $this->route('rfq');
            if ($rfq && $rfq->bids()->where('company_id', $this->company_id)->exists()) {
                $validator->errors()->add('company_id', 'Ваша компания уже подала заявку на этот RFQ');
            }
        });
    }

    /**
     * Кастомные сообщения об ошибках
     */
    public function messages(): array
    {
        return [
            'company_id.required' => 'Выберите компанию-участника',
            'price.required' => 'Укажите цену предложения',
            'deadline.required' => 'Укажите срок выполнения',
            'advance_percent.required' => 'Укажите размер аванса',
            'advance_percent.max' => 'Размер аванса не может превышать 100%',
        ];
    }
}