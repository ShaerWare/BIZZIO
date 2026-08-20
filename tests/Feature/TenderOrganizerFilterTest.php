<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Rfq;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * #284 — Фильтр закупок по компании-организатору: подсказки при вводе и сама фильтрация.
 */
class TenderOrganizerFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['email_verified_at' => now()]);
    }

    private function companyWithRfq(string $companyName, string $rfqTitle): Company
    {
        $company = Company::factory()->create([
            'name' => $companyName,
            'created_by' => $this->user->id,
            'is_verified' => true,
        ]);

        Rfq::create([
            'number' => Rfq::generateNumber(),
            'title' => $rfqTitle,
            'description' => 'Описание',
            'company_id' => $company->id,
            'created_by' => $this->user->id,
            'type' => 'open',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(7),
            'weight_price' => 40,
            'weight_deadline' => 30,
            'weight_advance' => 30,
            'status' => 'active',
        ]);

        return $company;
    }

    /*
     * Регистр в запросах совпадает с названием компании: PostgreSQL ищет через ilike и регистр
     * не важен, но тесты идут на SQLite, где like приводит к нижнему регистру только ASCII.
     */
    public function test_organizer_suggestions_match_by_substring(): void
    {
        $this->companyWithRfq('СтройМонтаж', 'Закупка кабеля');
        $this->companyWithRfq('ТеплоСеть', 'Закупка труб');

        $response = $this->getJson(route('tenders.organizers', ['q' => 'Строй']));

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['name' => 'СтройМонтаж']);
    }

    public function test_organizer_suggestions_skip_companies_without_procurements(): void
    {
        Company::factory()->create(['name' => 'СтройБезЗакупок', 'created_by' => $this->user->id]);

        $response = $this->getJson(route('tenders.organizers', ['q' => 'Строй']));

        $response->assertOk();
        $response->assertJsonCount(0);
    }

    public function test_organizer_suggestions_require_two_characters(): void
    {
        $this->companyWithRfq('СтройМонтаж', 'Закупка кабеля');

        $this->getJson(route('tenders.organizers', ['q' => 'с']))
            ->assertOk()
            ->assertJsonCount(0);
    }

    public function test_tenders_filtered_by_selected_organizer_id(): void
    {
        $company = $this->companyWithRfq('СтройМонтаж', 'Закупка кабеля');
        $this->companyWithRfq('ТеплоСеть', 'Закупка труб');

        $response = $this->get(route('tenders.index', ['organizer_id' => $company->id]));

        $response->assertOk();
        $response->assertSee('Закупка кабеля');
        $response->assertDontSee('Закупка труб');
    }

    public function test_tenders_filtered_by_typed_organizer_name(): void
    {
        $this->companyWithRfq('СтройМонтаж', 'Закупка кабеля');
        $this->companyWithRfq('ТеплоСеть', 'Закупка труб');

        $response = $this->get(route('tenders.index', ['organizer' => 'Тепло']));

        $response->assertOk();
        $response->assertSee('Закупка труб');
        $response->assertDontSee('Закупка кабеля');
    }

    public function test_organizer_id_wins_over_typed_text(): void
    {
        $company = $this->companyWithRfq('СтройМонтаж', 'Закупка кабеля');
        $this->companyWithRfq('ТеплоСеть', 'Закупка труб');

        $response = $this->get(route('tenders.index', [
            'organizer' => 'Тепло',
            'organizer_id' => $company->id,
        ]));

        $response->assertOk();
        $response->assertSee('Закупка кабеля');
        $response->assertDontSee('Закупка труб');
    }

    public function test_filter_field_is_rendered_on_tenders_page(): void
    {
        $response = $this->get(route('tenders.index'));

        $response->assertOk();
        $response->assertSee('Компания-организатор');
    }
}
