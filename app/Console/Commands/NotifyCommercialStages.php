<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Rfq;
use App\Notifications\CommercialStageOneStartedNotification;
use App\Notifications\CommercialStageTwoSoonNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * #195 Уведомления о старте этапов коммерческого аукциона:
 *  — этап 1: в момент начала приёма предложений, администраторам и модераторам приглашённых компаний;
 *  — этап 2: за 30 минут до начала торгов, пользователям, подавшим предложения на этапе 1.
 *
 * Отметки об отправке хранятся на запросе цен (stage1_notified_at / stage2_notified_at), поэтому
 * повторные запуски команды не рассылают одно и то же дважды.
 */
class NotifyCommercialStages extends Command
{
    protected $signature = 'commercial:notify-stages';

    protected $description = 'Уведомляет участников о начале этапа 1 и о скором начале торгов этапа 2';

    /** За сколько минут до начала торгов предупреждаем участников. */
    private const STAGE_TWO_LEAD_MINUTES = 30;

    public function handle(): int
    {
        $stageOne = $this->notifyStageOneStarted();
        $stageTwo = $this->notifyStageTwoSoon();

        $this->info("Уведомлений о старте этапа 1: {$stageOne}, о скором этапе 2: {$stageTwo}.");

        return self::SUCCESS;
    }

    /**
     * Этап 1: приём предложений начался — уведомляем приглашённые компании.
     */
    private function notifyStageOneStarted(): int
    {
        $rfqs = Rfq::query()
            ->where('procedure', Rfq::PROCEDURE_COMMERCIAL)
            ->where('status', 'active')
            ->whereNull('stage1_notified_at')
            ->where('start_date', '<=', now())
            ->with('invitations.company.moderators')
            ->get();

        foreach ($rfqs as $rfq) {
            $recipients = $rfq->invitations
                ->flatMap(fn ($invitation) => $invitation->company?->moderators ?? collect())
                ->unique('id')
                ->values();

            if ($recipients->isNotEmpty()) {
                Notification::send($recipients, new CommercialStageOneStartedNotification($rfq));
                $this->line("Этап 1 {$rfq->number}: уведомлено получателей — {$recipients->count()}");
            }

            // Отмечаем и когда приглашённых нет (открытая процедура) — иначе перебираем каждую минуту.
            $rfq->forceFill(['stage1_notified_at' => now()])->saveQuietly();
        }

        return $rfqs->count();
    }

    /**
     * Этап 2: до начала торгов осталось не больше 30 минут — уведомляем подавших предложения.
     */
    private function notifyStageTwoSoon(): int
    {
        $rfqs = Rfq::query()
            ->where('procedure', Rfq::PROCEDURE_COMMERCIAL)
            ->whereIn('status', ['active', 'closed'])
            ->whereNull('stage2_notified_at')
            ->whereNotNull('trading_start')
            ->where('trading_start', '<=', now()->addMinutes(self::STAGE_TWO_LEAD_MINUTES))
            ->with('bids.user')
            ->get();

        foreach ($rfqs as $rfq) {
            $recipients = $rfq->bids
                ->map(fn ($bid) => $bid->user)
                ->filter()
                ->unique('id')
                ->values();

            if ($recipients->isNotEmpty()) {
                Notification::send($recipients, new CommercialStageTwoSoonNotification($rfq));
                $this->line("Этап 2 {$rfq->number}: уведомлено получателей — {$recipients->count()}");
            }

            // Предложений может не быть вовсе — этап 2 тогда не состоится, но отметку ставим,
            // чтобы не перебирать процедуру каждую минуту.
            $rfq->forceFill(['stage2_notified_at' => now()])->saveQuietly();
        }

        return $rfqs->count();
    }
}
