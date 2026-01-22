<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAuctionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $auction = $this->route('auction');
        
        // Только создатель или модератор компании может редактировать
        // И только если статус = 'draft'
        return $auction->status === 'draft' && $auction->canManage(auth()->user());
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'trading_start' => ['required', 'date', 'after:end_date'],
            'starting_price' => ['required', 'numeric', 'min:1'],
            'technical_specification' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Название аукциона обязательно.',
            'end_date.required' => 'Укажите дату окончания приёма заявок.',
            'end_date.after' => 'Дата окончания должна быть позже даты начала.',
            'trading_start.required' => 'Укажите дату начала торгов.',
            'trading_start.after' => 'Дата начала торгов должна быть после окончания приёма заявок.',
            'starting_price.required' => 'Укажите начальную (максимальную) цену.',
            'starting_price.min' => 'Начальная цена должна быть больше нуля.',
            'technical_specification.mimes' => 'Техническое задание должно быть в формате PDF.',
            'technical_specification.max' => 'Размер файла не должен превышать 10 МБ.',
        ];
    }
}