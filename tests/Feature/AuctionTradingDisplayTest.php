<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Auction;
use App\Models\Company;
use App\Models\Rfq;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * #269/#270 Отображение параметров торгов: НМЦ без переносов, явные шаги и максимумы
 * срока/аванса на этапе 1 (Запрос цен) и во время торгов (Аукцион).
 */
class AuctionTradingDisplayTest extends TestCase
{
    use RefreshDatabase;

    private const NBSP = "\u{00A0}";

    private User $user;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $this->company = Company::factory()->create([
            'created_by' => $this->user->id,
            'is_verified' => true,
        ]);
        $this->company->assignModerator($this->user, 'owner');

        Storage::fake('public');
        Queue::fake();
    }

    private function createCommercialRfq(): Rfq
    {
        return Rfq::create([
            'number' => Rfq::generateNumber(),
            'title' => 'Коммерческий аукцион',
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'type' => 'open',
            'procedure' => Rfq::PROCEDURE_COMMERCIAL,
            'currency' => 'RUB',
            'status' => 'active',
            'start_date' => now()->subHour(),
            'end_date' => now()->addDay(),
            'weight_price' => 70,
            'weight_deadline' => 20,
            'weight_advance' => 10,
            'trading_start' => now()->addDays(2),
            'step_price' => 0.5,
            'step_deadline' => 3,
            'step_advance' => 5,
            'max_deadline' => 90,
            'max_advance' => 100,
            'is_results_hidden' => true,
        ]);
    }

    private function createCommercialAuction(): Auction
    {
        return Auction::create([
            'number' => Auction::generateNumber(),
            'title' => 'Коммерческий аукцион — торги',
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'type' => 'open',
            'procedure' => Auction::PROCEDURE_COMMERCIAL,
            'currency' => 'RUB',
            'status' => 'active',
            'start_date' => now()->subHour(),
            'end_date' => now()->addDay(),
            'trading_start' => now()->addDay(),
            'starting_price' => 1234567.89,
            'step_percent' => 0.5,
            'weight_price' => 70,
            'weight_deadline' => 20,
            'weight_advance' => 10,
            'step_price' => 0.5,
            'step_deadline' => 3,
            'step_advance' => 5,
            'max_deadline' => 90,
            'max_advance' => 100,
        ]);
    }

    public function test_starting_price_is_rendered_without_breakable_spaces(): void
    {
        $auction = $this->createCommercialAuction();

        $response = $this->actingAs($this->user)->get(route('auctions.show', $auction));

        $response->assertOk();
        // #269 Разряды и символ валюты соединены неразрывными пробелами — цифра не рвётся на строки.
        $response->assertSee('1'.self::NBSP.'234'.self::NBSP.'567,89'.self::NBSP.'₽', false);
    }

    public function test_stage_one_page_shows_steps_and_maximums(): void
    {
        $rfq = $this->createCommercialRfq();

        $response = $this->actingAs($this->user)->get(route('rfqs.show', $rfq));

        $response->assertOk();
        $response->assertSee('Параметры торгов (этап 2)');
        $response->assertSee('Шаг изменения срока');
        $response->assertSee('Макс. срок выполнения');
        $response->assertSee('Макс. размер аванса');
    }

    public function test_auction_page_shows_steps_and_maximums(): void
    {
        $auction = $this->createCommercialAuction();

        $response = $this->actingAs($this->user)->get(route('auctions.show', $auction));

        $response->assertOk();
        $response->assertSee('Параметры торгов (этап 2)');
        $response->assertSee('Шаг изменения цены');
        $response->assertSee('Макс. срок выполнения');
    }

    public function test_standard_auction_does_not_show_commercial_parameters(): void
    {
        $auction = $this->createCommercialAuction();
        $auction->update(['procedure' => Auction::PROCEDURE_STANDARD]);

        $response = $this->actingAs($this->user)->get(route('auctions.show', $auction));

        $response->assertOk();
        $response->assertDontSee('Параметры торгов (этап 2)');
    }
}
