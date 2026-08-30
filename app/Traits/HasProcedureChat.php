<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Company;
use App\Models\ProcedureChatMessage;
use App\Models\ProcedureParticipant;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

/**
 * #218 Чат процедуры (этап 1) и отстранение участников.
 *
 * Подключается к Rfq и Auction: обе модели ведут чат вопросов-ответов, обезличенный
 * для участников, и хранят состояние отстранения компании от участия.
 */
trait HasProcedureChat
{
    public function chatMessages(): MorphMany
    {
        return $this->morphMany(ProcedureChatMessage::class, 'procedure')->orderBy('id');
    }

    public function chatParticipants(): MorphMany
    {
        return $this->morphMany(ProcedureParticipant::class, 'procedure');
    }

    /**
     * Запись участника с обезличенным кодом — создаётся при первом обращении.
     *
     * Код умышленно другого формата, чем anonymous_code торгов этапа 2 («У-01» против «AB12»),
     * чтобы по переписке нельзя было опознать автора ставок.
     */
    public function chatParticipantFor(Company $company): ProcedureParticipant
    {
        $existing = $this->chatParticipants()->where('company_id', $company->id)->first();

        if ($existing) {
            return $existing;
        }

        // Код выдаётся по порядку появления в чате, в рамках одной процедуры.
        $next = $this->chatParticipants()->count() + 1;

        do {
            $code = 'У-'.str_pad((string) $next, 2, '0', STR_PAD_LEFT);
            $taken = $this->chatParticipants()->where('chat_code', $code)->exists();
            $next++;
        } while ($taken);

        return $this->chatParticipants()->create([
            'company_id' => $company->id,
            'chat_code' => $code,
        ]);
    }

    /**
     * Карта «id компании → обезличенный код» — чтобы не дёргать БД на каждое сообщение.
     *
     * @return array<int, string>
     */
    public function chatCodeMap(): array
    {
        return $this->chatParticipants()
            ->pluck('chat_code', 'company_id')
            ->all();
    }

    /**
     * Запись об отстранении одной из компаний пользователя — чтобы показать ему причину.
     */
    public function bannedParticipantFor(?User $user): ?ProcedureParticipant
    {
        if (! $user) {
            return null;
        }

        return $this->chatParticipants()
            ->whereIn('company_id', $user->moderatedCompanies->pluck('id'))
            ->whereNotNull('banned_at')
            ->first();
    }

    /**
     * Отстранена ли компания от участия в процедуре.
     */
    public function isCompanyBanned(int $companyId): bool
    {
        return $this->chatParticipants()
            ->where('company_id', $companyId)
            ->whereNotNull('banned_at')
            ->exists();
    }

    /**
     * ID отстранённых компаний — используется для исключения их заявок из результатов.
     *
     * @return Collection<int, int>
     */
    public function bannedCompanyIds(): Collection
    {
        return $this->chatParticipants()
            ->whereNotNull('banned_at')
            ->pluck('company_id');
    }

    /**
     * #295 Открыт ли чат для компаний, ещё не подавших заявку.
     *
     * У открытой процедуры вопрос можно задать до подачи заявки — именно для того, чтобы
     * решить, участвовать ли. У закрытой состав ограничен приглашёнными, поэтому там
     * прежнее правило #218 сохраняется.
     */
    public function chatOpenToProspects(): bool
    {
        return $this->type === 'open' && $this->isChatOpen();
    }

    /**
     * Компании пользователя, допущенные к чату этой процедуры (не отстранённые).
     *
     * #218 Это участники: подавшие заявку или приглашённые.
     * #295 Плюс — пока идёт этап 1 открытой процедуры — любая компания пользователя:
     * вопрос задают до подачи заявки. Компании, успевшие написать, сохраняют доступ
     * к переписке и после закрытия приёма заявок.
     *
     * @return Collection<int, Company>
     */
    public function chatCompaniesFor(?User $user): Collection
    {
        if (! $user) {
            return collect();
        }

        // Компания-организатор участвует в чате как организатор, а не как участник.
        $companies = $user->moderatedCompanies
            ->reject(fn (Company $c) => (int) $c->id === (int) $this->company_id)
            ->reject(fn (Company $c) => $this->isCompanyBanned($c->id));

        if ($this->chatOpenToProspects()) {
            return $companies->values();
        }

        $allowedIds = $this->chatParticipantCompanyIds()
            ->merge($this->chatParticipants()->pluck('company_id'))
            ->unique();

        return $companies->whereIn('id', $allowedIds->all())->values();
    }

    /**
     * #295 Компании, которых организатор видит в списке чата: участники процедуры
     * плюс те, кто писал в чат, не подав заявку (их тоже нужно иметь возможность отстранить).
     *
     * @return Collection<int, int>
     */
    public function chatVisibleCompanyIds(): Collection
    {
        return $this->chatParticipantCompanyIds()
            ->merge($this->chatParticipants()->pluck('company_id'))
            ->unique()
            ->values();
    }

    /**
     * Может ли пользователь читать чат: организатор или участник процедуры.
     */
    public function canReadChat(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->canManage($user) || $this->chatCompaniesFor($user)->isNotEmpty();
    }
}
