<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Auction;

class StoreAuctionBidRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $auction = $this->route('auction');
        $companyId = $this->input('company_id');
        
        // Проверки:
        // 1. Пользователь должен быть модератором выбранной компании
        $company = \App\Models\Company::find($companyId);
        if (!$company || !$company->isModerator(auth()->user())) {
            return false;
        }
        
        // 2. Компания не должна быть организатором
        if ($auction->company_id === $companyId) {
            return false;
        }
        
        // 3. Для закрытых процедур — проверка приглашения
        if ($auction->type === 'closed') {
            $hasInvitation = $auction->invitations()
                ->where('company_id', $companyId)
                ->exists();
            
            if (!$hasInvitation) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $auction = $this->route('auction');
        
        // Определяем тип ставки
        $isInitialBid = !$auction->isTrading();
        
        if ($isInitialBid) {
            // Правила для заявки на участие
            return [
                'company_id' => ['required', 'exists:companies,id'],
                'comment' => ['nullable', 'string', 'max:1000'],
                'agreement' => ['required', 'accepted'],
            ];
        } else {
            // Правила для ставки в торгах
            $currentPrice = $auction->getCurrentPrice();
            $stepRange = $auction->getStepRange();
            
            return [
                'company_id' => ['required', 'exists:companies,id'],
                'price' => [
                    'required',
                    'numeric',
                    'lt:' . $currentPrice, // Цена должна быть меньше текущей
                    'gte:' . ($currentPrice - $stepRange['max']), // Не меньше чем (текущая - макс. шаг)
                    'lte:' . ($currentPrice - $stepRange['min']), // Не больше чем (текущая - мин. шаг)
                ],
                'comment' => ['nullable', 'string', 'max:500'],
            ];
        }
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        $auction = $this->route('auction');
        $currentPrice = $auction->getCurrentPrice();
        $stepRange = $auction->getStepRange();
        
        return [
            'company_id.required' => 'Необходимо выбрать компанию.',
            'company_id.exists' => 'Выбранная компания не существует.',
            'price.required' => 'Укажите предлагаемую цену.',
            'price.lt' => 'Цена должна быть меньше текущей (' . number_format($currentPrice, 2) . ' ₽).',
            'price.gte' => 'Снижение цены не должно превышать ' . number_format($stepRange['max'], 2) . ' ₽ (5%).',
            'price.lte' => 'Снижение цены должно быть не менее ' . number_format($stepRange['min'], 2) . ' ₽ (0.5%).',
            'agreement.accepted' => 'Необходимо подтвердить согласие с условиями.',
        ];
    }
}