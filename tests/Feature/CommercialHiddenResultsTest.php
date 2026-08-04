<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Auction;
use App\Models\AuctionBid;
use App\Models\Company;
use App\Models\User;
use App\Services\CommercialAuctionScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * #232 Скрытые результаты в коммерческом аукционе (модель «непрерывного лидерства»).
 * Участнику не показываем детали лидера/историю, но отдаём числовой «балл для лидерства»
 * (best_score) — цель, которую нужно превзойти. Организатор видит всё.
 */
class CommercialHiddenResultsTest extends TestCase
{
    use RefreshDatabase;

    private User $organizer;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->organizer = User::factory()->create(['email_verified_at' => now()]);
        $this->company = Company::factory()->create(['created_by' => $this->organizer->id, 'is_verified' => true]);
        $this->company->assignModerator($this->organizer, 'owner');
        Storage::fake('public');
        Queue::fake();
    }

    /** @return array{0: User, 1: Company} */
    private function verifiedBidder(): array
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $company = Company::factory()->create(['created_by' => $user->id, 'is_verified' => true]);
        $company->assignModerator($user, 'owner');

        return [$user, $company];
    }

    private function hiddenTradingAuction(): Auction
    {
        return Auction::create([
            'number' => Auction::generateNumber(),
            'title' => 'КА скрытый',
            'company_id' => $this->company->id,
            'created_by' => $this->organizer->id,
            'type' => 'closed',
            'procedure' => Auction::PROCEDURE_COMMERCIAL,
            'start_date' => now()->subDay(),
            'end_date' => now()->subDay(),
            'trading_start' => now()->subHour(),
            'trading_end' => now()->addDay(),
            'starting_price' => 1_200_000,
            'weight_price' => 70, 'weight_deadline' => 20, 'weight_advance' => 10,
            'step_price' => 0.5, 'step_deadline' => 1, 'step_advance' => 5,
            'max_deadline' => 100, 'max_advance' => 50,
            'is_results_hidden' => true,
            'status' => 'trading',
        ]);
    }

    /** @return array{0: User, 1: Company} */
    private function participant(Auction $auction): array
    {
        [$user, $company] = $this->verifiedBidder();
        $auction->invitations()->create(['company_id' => $company->id, 'status' => 'accepted']);

        return [$user, $company];
    }

    private function seedLeader(Auction $auction, Company $company, User $user): AuctionBid
    {
        $scoring = app(CommercialAuctionScoringService::class);
        $offer = new AuctionBid([
            'auction_id' => $auction->id, 'company_id' => $company->id, 'user_id' => $user->id,
            'type' => 'offer', 'price' => 900_000, 'deadline' => 50, 'advance_percent' => 20,
            'anonymous_code' => 'LD01', 'status' => 'pending', 'became_best_at' => now(),
        ]);
        $scoring->fillScores($auction, $offer);
        $offer->save();
        $auction->update(['best_bid_id' => $offer->id, 'last_bid_at' => now()]);

        return $offer;
    }

    public function test_participant_sees_trading_even_when_results_hidden(): void
    {
        // #237 Скрытые результаты НЕ прячут ход торгов от участников этапа 2 — они видят лидера/историю.
        $auction = $this->hiddenTradingAuction();
        [$leadUser, $leadCompany] = $this->participant($auction);
        $this->seedLeader($auction, $leadCompany, $leadUser);

        [$viewer] = $this->participant($auction);

        $this->actingAs($viewer)
            ->getJson(route('auctions.state', $auction))
            ->assertOk()
            ->assertJsonPath('procedure', 'commercial')
            ->assertJsonPath('results_hidden', false)
            ->assertJsonPath('best_offer.price', 900000)
            ->assertJsonPath('best_score', fn ($v) => is_numeric($v) && $v > 0);
    }

    public function test_outsider_sees_hidden_results_on_open_auction(): void
    {
        // #237 Посторонний (не участник, не организатор) на открытом аукционе со скрытыми
        // результатами видит только целевой балл — детали лидера и история скрыты.
        $auction = $this->hiddenTradingAuction();
        $auction->update(['type' => 'open']); // чтобы посторонний мог просматривать
        [$leadUser, $leadCompany] = $this->participant($auction);
        $this->seedLeader($auction, $leadCompany, $leadUser);

        [$outsider] = $this->verifiedBidder(); // верифицирован, но НЕ приглашён

        $this->actingAs($outsider)
            ->getJson(route('auctions.state', $auction))
            ->assertOk()
            ->assertJsonPath('results_hidden', true)
            ->assertJsonPath('best_offer', null)
            ->assertJsonPath('best_offer_history', [])
            ->assertJsonPath('best_score', fn ($v) => is_numeric($v) && $v > 0);
    }

    public function test_organizer_state_sees_full_results_even_when_hidden(): void
    {
        $auction = $this->hiddenTradingAuction();
        [$leadUser, $leadCompany] = $this->participant($auction);
        $this->seedLeader($auction, $leadCompany, $leadUser);

        $this->actingAs($this->organizer)
            ->getJson(route('auctions.state', $auction))
            ->assertOk()
            ->assertJsonPath('results_hidden', false)
            ->assertJsonPath('best_offer.price', 900000);
    }

    public function test_participant_can_beat_hidden_leader_with_better_offer(): void
    {
        $auction = $this->hiddenTradingAuction();
        [$leadUser, $leadCompany] = $this->participant($auction);
        $this->seedLeader($auction, $leadCompany, $leadUser);
        $bestBefore = $auction->fresh()->best_bid_id;

        [$user, $company] = $this->participant($auction);

        // Строго лучше лидера — принимается, несмотря на скрытые результаты.
        $this->actingAs($user)->post(route('auctions.offers.store', $auction), [
            'company_id' => $company->id, 'price' => 800_000, 'deadline' => 40, 'advance_percent' => 10,
        ])->assertRedirect();

        $this->assertNotSame($bestBefore, $auction->fresh()->best_bid_id);
    }

    public function test_organizer_sees_explicit_no_participation_message(): void
    {
        // #232 Организатор своего аукциона не видит форму подачи, а видит явное пояснение.
        $auction = $this->hiddenTradingAuction();
        $this->participant($auction); // хотя бы один участник, чтобы аукцион был осмысленным

        $this->actingAs($this->organizer)
            ->get(route('auctions.show', $auction))
            ->assertOk()
            ->assertSee('Вы организатор этой процедуры')
            ->assertDontSee('Подать предложение')
            ->assertDontSee('Настройка предложения');
    }

    public function test_participant_sees_offer_form(): void
    {
        $auction = $this->hiddenTradingAuction();
        [$user] = $this->participant($auction);

        $this->actingAs($user)
            ->get(route('auctions.show', $auction))
            ->assertOk()
            ->assertSee('Настройка предложения')
            ->assertSee('Подать предложение')
            ->assertDontSee('Вы организатор этой процедуры');
    }

    public function test_offer_form_inputs_do_not_use_hard_step_binding(): void
    {
        // #232 Регресс: у полей срок/аванс НЕ должно быть жёсткого организаторского шага
        // как HTML-атрибута step — иначе дефолт (=max_deadline/max_advance) мог не совпасть
        // с шагом при базе min и HTML5-валидация молча блокировала сабмит формы предложения.
        // Берём max_deadline, кратный шагу «неудобно» (чётный max при чётном шаге и базе min=1).
        $auction = $this->hiddenTradingAuction();
        $auction->update(['max_deadline' => 30, 'step_deadline' => 2, 'max_advance' => 100, 'step_advance' => 10]);
        [$user] = $this->participant($auction);

        $resp = $this->actingAs($user)->get(route('auctions.show', $auction))->assertOk();
        // Жёсткого биндинга шага быть не должно.
        $resp->assertDontSee(':step="steps.d"', false);
        $resp->assertDontSee(':step="steps.a"', false);
        // step="any" убран у аванса — иначе слайдер давал «кучу цифр» после запятой.
        $resp->assertDontSee('step="any"', false);
        // Поле срока — целочисленный шаг 1; поле аванса — 0.01 (2 знака, без биллиардных дробей).
        $resp->assertSee('step="1"', false);
        $resp->assertSee('step="0.01"', false);
    }

    public function test_worse_offer_still_rejected_with_hidden_results(): void
    {
        $auction = $this->hiddenTradingAuction();
        [$leadUser, $leadCompany] = $this->participant($auction);
        $this->seedLeader($auction, $leadCompany, $leadUser);
        $bestBefore = $auction->fresh()->best_bid_id;

        [$user, $company] = $this->participant($auction);

        // Заведомо хуже — сервер отклоняет (модель лидерства сохраняется).
        $this->actingAs($user)->post(route('auctions.offers.store', $auction), [
            'company_id' => $company->id, 'price' => 1_150_000, 'deadline' => 90, 'advance_percent' => 45,
        ])->assertSessionHas('error');

        $this->assertSame($bestBefore, $auction->fresh()->best_bid_id);
    }

    // =========================================================
    // #257 — шаг как минимальное улучшение и запрет самоперебивания
    // =========================================================

    public function test_company_cannot_outbid_its_own_leading_offer(): void
    {
        $auction = $this->hiddenTradingAuction();
        [$user, $company] = $this->participant($auction);
        $this->seedLeader($auction, $company, $user);
        $bestBefore = $auction->fresh()->best_bid_id;

        // Заведомо лучше собственного лидера — всё равно отклоняется.
        $this->actingAs($user)->post(route('auctions.offers.store', $auction), [
            'company_id' => $company->id, 'price' => 700_000, 'deadline' => 30, 'advance_percent' => 5,
        ])->assertSessionHas('error');

        $this->assertSame($bestBefore, $auction->fresh()->best_bid_id);
        $this->assertSame(1, $auction->fresh()->offerBids()->count());
    }

    public function test_improvement_smaller_than_step_is_rejected(): void
    {
        // Шаги аукциона: цена 0.5% от НМЦ 1 200 000 = 6 000, срок 1 дн., аванс 5 %.
        // Лидер: 900 000 / 50 дн. / 20 %. Улучшаем аванс всего на 1 п.п. — меньше шага.
        $auction = $this->hiddenTradingAuction();
        [$leadUser, $leadCompany] = $this->participant($auction);
        $this->seedLeader($auction, $leadCompany, $leadUser);
        $bestBefore = $auction->fresh()->best_bid_id;

        [$user, $company] = $this->participant($auction);

        $this->actingAs($user)->post(route('auctions.offers.store', $auction), [
            'company_id' => $company->id, 'price' => 900_000, 'deadline' => 50, 'advance_percent' => 19,
        ])->assertSessionHas('error');

        $this->assertSame($bestBefore, $auction->fresh()->best_bid_id);
    }

    public function test_improvement_of_exactly_one_step_is_accepted(): void
    {
        $auction = $this->hiddenTradingAuction();
        [$leadUser, $leadCompany] = $this->participant($auction);
        $this->seedLeader($auction, $leadCompany, $leadUser);
        $bestBefore = $auction->fresh()->best_bid_id;

        [$user, $company] = $this->participant($auction);

        // Аванс 20 % → 15 % (ровно один шаг), остальное как у лидера.
        $this->actingAs($user)->post(route('auctions.offers.store', $auction), [
            'company_id' => $company->id, 'price' => 900_000, 'deadline' => 50, 'advance_percent' => 15,
        ])->assertRedirect();

        $this->assertNotSame($bestBefore, $auction->fresh()->best_bid_id);
    }

    public function test_price_improvement_smaller_than_step_is_rejected(): void
    {
        // Шаг цены = 0.5 % от НМЦ 1 200 000 = 6 000. Снижение на 1 000 — меньше шага.
        $auction = $this->hiddenTradingAuction();
        [$leadUser, $leadCompany] = $this->participant($auction);
        $this->seedLeader($auction, $leadCompany, $leadUser);

        [$user, $company] = $this->participant($auction);

        $this->actingAs($user)->post(route('auctions.offers.store', $auction), [
            'company_id' => $company->id, 'price' => 899_000, 'deadline' => 50, 'advance_percent' => 20,
        ])->assertSessionHas('error');
    }

    public function test_worsening_a_criterion_is_not_limited_by_step(): void
    {
        // Шаг ограничивает только улучшение: аванс можно ухудшить на 1 п.п.,
        // компенсировав это ценой (снижение больше шага).
        $auction = $this->hiddenTradingAuction();
        [$leadUser, $leadCompany] = $this->participant($auction);
        $this->seedLeader($auction, $leadCompany, $leadUser);
        $bestBefore = $auction->fresh()->best_bid_id;

        [$user, $company] = $this->participant($auction);

        $this->actingAs($user)->post(route('auctions.offers.store', $auction), [
            'company_id' => $company->id, 'price' => 850_000, 'deadline' => 50, 'advance_percent' => 21,
        ])->assertRedirect();

        $this->assertNotSame($bestBefore, $auction->fresh()->best_bid_id);
    }

    public function test_first_offer_is_not_limited_by_step(): void
    {
        // Лидера нет — сравнивать не с чем, любое корректное предложение принимается.
        $auction = $this->hiddenTradingAuction();
        [$user, $company] = $this->participant($auction);

        $this->actingAs($user)->post(route('auctions.offers.store', $auction), [
            'company_id' => $company->id, 'price' => 1_199_999, 'deadline' => 99, 'advance_percent' => 49,
        ])->assertRedirect();

        $this->assertNotNull($auction->fresh()->best_bid_id);
    }

    public function test_trading_form_shows_participant_code_next_to_history(): void
    {
        $auction = $this->hiddenTradingAuction();
        [$user] = $this->participant($auction);

        $this->actingAs($user)
            ->get(route('auctions.show', $auction))
            ->assertOk()
            ->assertSee('Ваш код участника:')
            ->assertSee('x-text="myCode"', false);
    }

    // =========================================================
    // #237 — у коммерческой процедуры результаты скрыты по умолчанию
    // =========================================================

    public function test_commercial_rfq_is_created_with_hidden_results_without_checkbox(): void
    {
        $this->actingAs($this->organizer)
            ->post(route('rfqs.store'), [
                'title' => 'КА без галочки',
                'company_id' => $this->company->id,
                'type' => 'open',
                'procedure' => 'commercial',
                'currency' => 'RUB',
                'status' => 'draft',
                'start_date' => now()->format('Y-m-d H:i:s'),
                'end_date' => now()->addDay()->format('Y-m-d H:i:s'),
                'weight_price' => 70, 'weight_deadline' => 20, 'weight_advance' => 10,
                'trading_start' => now()->addDay()->addHour()->format('Y-m-d H:i:s'),
                'step_price' => 0.5, 'step_deadline' => 1, 'step_advance' => 5,
                'max_deadline' => 90, 'max_advance' => 100,
                'technical_specification' => \Illuminate\Http\UploadedFile::fake()->createWithContent('tz.pdf', '%PDF-1.4 test'),
                // Галочка НЕ передаётся — в форме коммерческого аукциона её нет.
            ])
            ->assertRedirect();

        $rfq = \App\Models\Rfq::where('title', 'КА без галочки')->firstOrFail();

        $this->assertTrue($rfq->is_results_hidden);
    }

    public function test_standard_rfq_keeps_checkbox_behaviour(): void
    {
        $this->actingAs($this->organizer)
            ->post(route('rfqs.store'), [
                'title' => 'Обычный запрос цен',
                'company_id' => $this->company->id,
                'type' => 'open',
                'procedure' => 'standard',
                'currency' => 'RUB',
                'status' => 'draft',
                'start_date' => now()->format('Y-m-d H:i:s'),
                'end_date' => now()->addDay()->format('Y-m-d H:i:s'),
                'weight_price' => 50, 'weight_deadline' => 30, 'weight_advance' => 20,
                'technical_specification' => \Illuminate\Http\UploadedFile::fake()->createWithContent('tz.pdf', '%PDF-1.4 test'),
            ])
            ->assertRedirect();

        $this->assertFalse(\App\Models\Rfq::where('title', 'Обычный запрос цен')->firstOrFail()->is_results_hidden);
    }

    public function test_commercial_draft_edit_cannot_unhide_results(): void
    {
        $rfq = \App\Models\Rfq::create([
            'number' => \App\Models\Rfq::generateNumber(),
            'title' => 'КА черновик',
            'company_id' => $this->company->id,
            'created_by' => $this->organizer->id,
            'type' => 'open',
            'procedure' => \App\Models\Rfq::PROCEDURE_COMMERCIAL,
            'currency' => 'RUB',
            'start_date' => now()->addDay(),
            'end_date' => now()->addDays(2),
            'trading_start' => now()->addDays(2)->addHour(),
            'weight_price' => 70, 'weight_deadline' => 20, 'weight_advance' => 10,
            'step_price' => 0.5, 'step_deadline' => 1, 'step_advance' => 5,
            'max_deadline' => 90, 'max_advance' => 100,
            'is_results_hidden' => true,
            'status' => 'draft',
        ]);

        // Форма коммерческого аукциона галочку не выводит...
        $this->actingAs($this->organizer)
            ->get(route('rfqs.edit', $rfq))
            ->assertOk()
            ->assertDontSee('name="is_results_hidden"', false);

        // ...и подделанный запрос без неё флаг не снимает.
        $this->actingAs($this->organizer)
            ->put(route('rfqs.update', $rfq), ['title' => 'КА черновик'])
            ->assertRedirect();

        $this->assertTrue($rfq->fresh()->is_results_hidden);
    }

    public function test_closed_commercial_auction_hides_results_from_outsider_but_not_participant(): void
    {
        $auction = $this->hiddenTradingAuction();
        $auction->update(['type' => 'open']); // чтобы посторонний мог открыть страницу
        [$leadUser, $leadCompany] = $this->participant($auction);
        $this->seedLeader($auction, $leadCompany, $leadUser);
        $auction->update(['status' => 'closed', 'winner_bid_id' => $auction->fresh()->best_bid_id]);

        // Посторонний — результаты скрыты.
        $outsider = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($outsider)
            ->get(route('auctions.show', $auction))
            ->assertOk()
            ->assertSee('Результаты скрыты организатором');

        // Организатор и участник — видят.
        $this->actingAs($this->organizer)
            ->get(route('auctions.show', $auction))
            ->assertOk()
            ->assertDontSee('Результаты скрыты организатором');

        $this->actingAs($leadUser)
            ->get(route('auctions.show', $auction))
            ->assertOk()
            ->assertDontSee('Результаты скрыты организатором');
    }

    public function test_closed_commercial_auction_visible_to_invited_company_without_offers(): void
    {
        // #237 Участник этапа 1, не подавший ставку на этапе 2, всё равно видит итоги.
        $auction = $this->hiddenTradingAuction();
        [$leadUser, $leadCompany] = $this->participant($auction);
        $this->seedLeader($auction, $leadCompany, $leadUser);
        [$silentUser] = $this->participant($auction);
        $auction->update(['status' => 'closed', 'winner_bid_id' => $auction->fresh()->best_bid_id]);

        $this->actingAs($silentUser)
            ->get(route('auctions.show', $auction))
            ->assertOk()
            ->assertDontSee('Результаты скрыты организатором');
    }
}
