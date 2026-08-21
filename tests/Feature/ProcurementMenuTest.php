<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * #282 — Меню «Закупки»: пункты «Создать аукцион» и «Создать запрос цен» убраны,
 * размещение идёт через единственный пункт «Создать закупку» (двухэтапный
 * коммерческий аукцион).
 */
class ProcurementMenuTest extends TestCase
{
    use RefreshDatabase;

    private function moderator(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $company = Company::factory()->create(['created_by' => $user->id]);
        $company->assignModerator($user, 'owner');

        return $user;
    }

    public function test_menu_has_create_procurement_instead_of_auction_and_rfq(): void
    {
        $response = $this->actingAs($this->moderator())->get('/tenders');

        $response->assertOk();
        $response->assertSee('Создать закупку');
        $response->assertDontSee('Создать аукцион');
        $response->assertDontSee('Создать запрос цен');
        $response->assertDontSee('Создать коммерческий аукцион');
    }

    public function test_create_procurement_points_to_commercial_procedure(): void
    {
        $response = $this->actingAs($this->moderator())->get('/tenders');

        $response->assertSee(route('rfqs.create', ['procedure' => 'commercial']), false);
    }

    public function test_menu_contains_all_required_items(): void
    {
        $response = $this->actingAs($this->moderator())->get('/tenders');

        foreach (['Найти закупку', 'Создать закупку', 'Мои закупки', 'Мои приглашения', 'Мои заявки', 'Правила закупок'] as $item) {
            $response->assertSee($item);
        }
    }

    public function test_user_without_company_does_not_see_organizer_items(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);

        // Рендерим само меню: на дашборде «Мои закупки» есть ещё и в виджете.
        $menu = view('layouts.navigation')->render();

        $this->assertStringContainsString('Найти закупку', $menu);
        $this->assertStringContainsString('Мои заявки', $menu);
        $this->assertStringNotContainsString('Создать закупку', $menu);
        $this->assertStringNotContainsString('Мои закупки', $menu);
    }

    public function test_tenders_page_offers_only_procurement_creation(): void
    {
        $response = $this->actingAs($this->moderator())->get('/tenders');

        $response->assertOk();
        $response->assertSee('Создать закупку');
        $response->assertDontSee('Разместить');
    }

    public function test_my_tenders_page_offers_only_procurement_creation(): void
    {
        $response = $this->actingAs($this->moderator())->get('/my-tenders');

        $response->assertOk();
        $response->assertSee('Создать закупку');
        $response->assertDontSee('Разместить');
    }
}
