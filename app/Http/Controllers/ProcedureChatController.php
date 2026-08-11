<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Auction;
use App\Models\Company;
use App\Models\ProcedureChatMessage;
use App\Models\Rfq;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * #218 Чат процедуры (этап 1): вопросы участников и ответы организатора.
 *
 * Чат виден только организатору и участникам. Участники обезличены: они видят коды
 * вида «У-01», отличные от кодов торгов этапа 2, и не видят названий компаний.
 * Организатор может отстранить компанию от участия с указанием причины.
 */
class ProcedureChatController extends Controller
{
    // Тонкие обёртки под implicit-биндинг Laravel: имя аргумента должно совпадать
    // с именем параметра маршрута, поэтому union-тип напрямую использовать нельзя.

    public function rfqIndex(Request $request, Rfq $rfq): JsonResponse
    {
        return $this->index($request, $rfq);
    }

    public function rfqStore(Request $request, Rfq $rfq): JsonResponse
    {
        return $this->store($request, $rfq);
    }

    public function rfqBan(Request $request, Rfq $rfq): JsonResponse
    {
        return $this->ban($request, $rfq);
    }

    public function auctionIndex(Request $request, Auction $auction): JsonResponse
    {
        return $this->index($request, $auction);
    }

    public function auctionStore(Request $request, Auction $auction): JsonResponse
    {
        return $this->store($request, $auction);
    }

    public function auctionBan(Request $request, Auction $auction): JsonResponse
    {
        return $this->ban($request, $auction);
    }

    /** Лента сообщений (используется поллингом). */
    private function index(Request $request, Rfq|Auction $procedure): JsonResponse
    {
        abort_unless($procedure->canReadChat($request->user()), 403);

        $isOrganizer = $procedure->canManage($request->user());
        $myCompanyIds = $procedure->chatCompaniesFor($request->user())->pluck('id');
        $codes = $procedure->chatCodeMap();

        $messages = $procedure->chatMessages()
            ->when($request->filled('after_id'), fn ($q) => $q->where('id', '>', (int) $request->input('after_id')))
            ->with('company')
            ->get()
            ->map(fn (ProcedureChatMessage $m) => $this->presentMessage($m, $codes, $isOrganizer, $myCompanyIds->all()));

        return response()->json([
            'messages' => $messages->values(),
            'is_organizer' => $isOrganizer,
            'can_post' => $this->canPost($procedure, $request),
            'my_code' => $isOrganizer ? null : $this->myCode($procedure, $request),
        ]);
    }

    /** Отправка сообщения. */
    private function store(Request $request, Rfq|Auction $procedure): JsonResponse
    {
        abort_unless($procedure->canReadChat($request->user()), 403);
        abort_unless($procedure->isChatOpen(), 422, 'Чат процедуры закрыт.');

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ], [
            'body.required' => 'Введите сообщение.',
            'body.max' => 'Сообщение не должно превышать 2000 символов.',
        ]);

        $isOrganizer = $procedure->canManage($request->user());
        $company = $isOrganizer ? null : $procedure->chatCompaniesFor($request->user())->first();

        abort_if(! $isOrganizer && ! $company, 403, 'Вы не участвуете в этой процедуре.');

        if ($company) {
            // Код выдаётся при первом сообщении и дальше не меняется.
            $procedure->chatParticipantFor($company);
        }

        $message = $procedure->chatMessages()->create([
            'user_id' => $request->user()->id,
            'company_id' => $company?->id,
            'is_organizer' => $isOrganizer,
            'body' => $data['body'],
        ]);

        $myCompanyIds = $company ? [$company->id] : [];

        return response()->json([
            'message' => $this->presentMessage($message->fresh('company'), $procedure->chatCodeMap(), $isOrganizer, $myCompanyIds),
        ], 201);
    }

    /**
     * Отстранение компании от участия: блокирует чат и подачу заявок,
     * аннулирует уже поданные заявки и снимает приглашение.
     */
    private function ban(Request $request, Rfq|Auction $procedure): JsonResponse
    {
        abort_unless($procedure->canManage($request->user()), 403);

        $data = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'reason' => ['required', 'string', 'max:1000'],
        ], [
            'reason.required' => 'Укажите причину отстранения.',
        ]);

        abort_if((int) $data['company_id'] === (int) $procedure->company_id, 422, 'Нельзя отстранить компанию-организатора.');

        $company = Company::findOrFail($data['company_id']);

        DB::transaction(function () use ($procedure, $company, $request, $data, &$participant) {
            $participant = $procedure->chatParticipantFor($company);
            $participant->update([
                'banned_at' => now(),
                'ban_reason' => $data['reason'],
                'banned_by' => $request->user()->id,
            ]);

            // Уже поданные заявки/предложения аннулируются — в результатах не участвуют.
            $procedure->bids()->where('company_id', $company->id)->update(['status' => 'rejected']);

            // Приглашение снимается — компания больше не участник процедуры.
            $procedure->invitations()->where('company_id', $company->id)->update(['status' => 'declined']);

            // Системная запись в чате — обезличенная, без названия компании.
            $procedure->chatMessages()->create([
                'user_id' => $request->user()->id,
                'company_id' => $company->id,
                'is_organizer' => true,
                'is_system' => true,
                'body' => 'Участник '.$participant->chat_code.' отстранён от участия в процедуре. Причина: '.$data['reason'],
            ]);
        });

        return response()->json([
            'success' => true,
            'chat_code' => $participant->chat_code,
        ]);
    }

    /**
     * Представление сообщения с учётом прав: названия компаний видит только организатор.
     *
     * @param  array<int, string>  $codes  company_id => обезличенный код
     * @param  array<int, int>  $myCompanyIds
     * @return array<string, mixed>
     */
    private function presentMessage(
        ProcedureChatMessage $message,
        array $codes,
        bool $isOrganizer,
        array $myCompanyIds
    ): array {
        $code = $message->company_id ? ($codes[$message->company_id] ?? null) : null;

        $isMine = $message->company_id !== null && in_array($message->company_id, $myCompanyIds, true);

        return [
            'id' => $message->id,
            'body' => $message->body,
            'is_organizer' => $message->is_organizer,
            'is_system' => $message->is_system,
            'is_mine' => $isMine,
            'author' => $message->is_system
                ? 'Система'
                : ($message->is_organizer ? 'Организатор' : ($code ?? 'Участник')),
            // Название компании — только организатору и только для сообщений участников.
            'company' => $isOrganizer && ! $message->is_organizer ? $message->company?->name : null,
            'company_id' => $isOrganizer ? $message->company_id : null,
            'chat_code' => $code,
            'time' => $message->created_at->format('d.m.Y H:i'),
        ];
    }

    private function canPost(Rfq|Auction $procedure, Request $request): bool
    {
        if (! $procedure->isChatOpen()) {
            return false;
        }

        return $procedure->canManage($request->user())
            || $procedure->chatCompaniesFor($request->user())->isNotEmpty();
    }

    private function myCode(Rfq|Auction $procedure, Request $request): ?string
    {
        $company = $procedure->chatCompaniesFor($request->user())->first();

        if (! $company) {
            return null;
        }

        return optional($procedure->chatParticipants()->where('company_id', $company->id)->first())->chat_code;
    }
}
