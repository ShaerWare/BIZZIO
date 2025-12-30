<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAuctionBidRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Проверку прав делаем в контроллере через Policy
        return true;
    }

    public function rules(): array
    {
        $auction = $this->route('auction');
        
        $rules = [
            'company_id' => 'required|exists:companies,id',
            'comment' => 'nullable|string|max:1000',
            'acknowledgement' => 'required|accepted',
        ];
        
        // Для торгов цена обязательна и должна быть в допустимом диапазоне
        if ($auction && $auction->isTrading()) {
            $currentPrice = $auction->getCurrentPrice();
            $stepRange = $auction->getStepRange();
            
            $rules['price'] = [
                'required',
                'numeric',
                'min:' . ($currentPrice - $stepRange['max']),
                'max:' . ($currentPrice - $stepRange['min']),
            ];
        } else {
            // Для заявок цена НЕ обязательна (используется стартовая)
            $rules['price'] = 'nullable|numeric|min:0';
        }
        
        return $rules;
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'Необходимо выбрать компанию.',
            'company_id.exists' => 'Выбранная компания не существует.',
            'acknowledgement.required' => 'Необходимо подтвердить согласие с условиями.',
            'acknowledgement.accepted' => 'Необходимо принять условия проведения аукциона.',
            'price.required' => 'Необходимо указать цену ставки.',
            'price.numeric' => 'Цена должна быть числом.',
            'price.min' => 'Ставка слишком низкая. Минимальная цена: :min ₽',
            'price.max' => 'Ставка слишком высокая. Максимальная цена: :max ₽',
            'comment.max' => 'Комментарий не может быть длиннее 1000 символов.',
        ];
    }
}