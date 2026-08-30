<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Rfq;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * #285 — Автопоиск в фильтрах закупок: кнопки «Применить» нет, форма отправляется сама,
 * кнопка «Сбросить» остаётся.
 */
class TenderAutoFilterTest extends TestCase
{
    use RefreshDatabase;

    private function seedRfq(string $title, string $status = 'active'): Rfq
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $company = Company::factory()->create(['created_by' => $user->id, 'is_verified' => true]);

        return Rfq::create([
            'number' => Rfq::generateNumber(),
            'title' => $title,
            'description' => 'Описание',
            'company_id' => $company->id,
            'created_by' => $user->id,
            'type' => 'open',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(7),
            'weight_price' => 40,
            'weight_deadline' => 30,
            'weight_advance' => 30,
            'status' => $status,
        ]);
    }

    public function test_apply_button_is_removed(): void
    {
        $response = $this->get(route('tenders.index'));

        $response->assertOk();
        $response->assertDontSee('Применить');
    }

    public function test_reset_button_remains(): void
    {
        $response = $this->get(route('tenders.index'));

        $response->assertOk();
        $response->assertSee('Сбросить');
    }

    public function test_filter_form_submits_itself(): void
    {
        $response = $this->get(route('tenders.index'));

        $response->assertOk();
        // Форма помечена для автопоиска, поля отправляют её сами.
        $response->assertSee('data-autofilter', false);
        $response->assertSee('$el.form.requestSubmit()', false);
    }

    public function test_filters_still_work_via_get_request(): void
    {
        $this->seedRfq('Закупка кабеля');
        $this->seedRfq('Закупка труб', 'closed');

        $response = $this->get(route('tenders.index', ['status' => 'active']));

        $response->assertOk();
        $response->assertSee('Закупка кабеля');
        $response->assertDontSee('Закупка труб');
    }

    public function test_search_filter_still_works(): void
    {
        $this->seedRfq('Закупка кабеля');
        $this->seedRfq('Закупка труб');

        $response = $this->get(route('tenders.index', ['search' => 'кабеля']));

        $response->assertOk();
        $response->assertSee('Закупка кабеля');
        $response->assertDontSee('Закупка труб');
    }
}
