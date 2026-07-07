<?php

namespace Tests\Feature;

use App\Models\Auction;
use App\Models\Company;
use App\Models\News;
use App\Models\Rfq;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Покрытие приоритетных задач, не охваченных профильными тест-классами.
 * (Остальные задачи покрыты: NotificationTest #143, CompanyTest #144/#137,
 *  FriendshipTest #142, RegistrationTest #145, SocialiteAvatarTest #134,
 *  AuctionTest/RfqTest #148, DashboardTest #149, SeoTest #152.)
 */
class PriorityTasksTest extends TestCase
{
    use RefreshDatabase;

    private function verifiedUser(array $attrs = []): User
    {
        return User::factory()->create(array_merge(['email_verified_at' => now()], $attrs));
    }

    // #139 — email не виден другим (скрыт из JSON-сериализации)
    public function test_139_user_email_is_hidden_from_serialization(): void
    {
        $user = User::factory()->create(['email' => 'private@example.com']);

        $this->assertArrayNotHasKey('email', $user->toArray());
        $this->assertStringNotContainsString('private@example.com', $user->toJson());
    }

    // #136 — старое меню убрано со страницы входа
    public function test_136_old_menu_removed_from_landing(): void
    {
        $this->get('/')
            ->assertStatus(200)
            ->assertDontSee('navbar-main', false);
    }

    // #140 — в виджете новостей нет картинок (только заголовки)
    public function test_140_news_widget_has_no_images(): void
    {
        $latestNews = collect([
            new News(['title' => 'Новость без картинки', 'link' => 'https://example.com/n', 'published_at' => now()]),
        ]);

        $html = view('partials.dashboard.news-widget', compact('latestNews'))->render();

        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('Новость без картинки', $html);
    }

    // #145 — поле «Фамилия»: accessor full_name и поле в профиле
    public function test_145_full_name_accessor(): void
    {
        $this->assertSame('Иван Петров', User::factory()->create(['name' => 'Иван', 'last_name' => 'Петров'])->full_name);
        $this->assertSame('Иван', User::factory()->create(['name' => 'Иван', 'last_name' => null])->full_name);
    }

    public function test_145_profile_has_last_name_field(): void
    {
        $this->actingAs($this->verifiedUser())
            ->get(route('profile.edit'))
            ->assertStatus(200)
            ->assertSee('name="last_name"', false);
    }

    // #146 — компонент кропа аватаров присутствует в формах
    public function test_146_avatar_cropper_on_profile_form(): void
    {
        $this->actingAs($this->verifiedUser())
            ->get(route('profile.edit'))
            ->assertSee('avatarCropper(', false);
    }

    public function test_146_avatar_cropper_on_company_create_form(): void
    {
        $this->actingAs($this->verifiedUser())
            ->get(route('companies.create'))
            ->assertSee('avatarCropper(', false);
    }

    // #150 — мобильное меню раскрывающееся (есть сворачиваемые группы)
    public function test_150_mobile_menu_has_collapsible_groups(): void
    {
        $this->get(route('companies.index'))
            ->assertStatus(200)
            ->assertSee('x-show="expanded"', false);
    }

    // #152 — SEO-разметка на публичной странице компании
    public function test_152_company_page_has_seo_markup(): void
    {
        $company = Company::factory()->create(['is_verified' => true]);

        $this->get(route('companies.show', $company))
            ->assertStatus(200)
            ->assertSee('og:title', false)
            ->assertSee('application/ld+json', false)
            ->assertSee('rel="canonical"', false);
    }

    // ==========================================
    // #182 — участвовать в закупках могут только верифицированные компании
    // ==========================================

    /** Создаёт организатора с верифицированной компанией и открытый RFQ. */
    private function openRfq(): Rfq
    {
        $owner = $this->verifiedUser();
        $company = Company::factory()->create(['created_by' => $owner->id, 'is_verified' => true]);
        $company->assignModerator($owner, 'owner');

        return Rfq::create([
            'number' => Rfq::generateNumber(),
            'title' => 'Тестовый RFQ',
            'description' => 'Описание',
            'company_id' => $company->id,
            'created_by' => $owner->id,
            'type' => 'open',
            'start_date' => now(),
            'end_date' => now()->addDays(7),
            'weight_price' => 40,
            'weight_deadline' => 30,
            'weight_advance' => 30,
            'status' => 'active',
        ]);
    }

    /** Создаёт организатора с верифицированной компанией и открытый аукцион (приём заявок). */
    private function openAuction(): Auction
    {
        $owner = $this->verifiedUser();
        $company = Company::factory()->create(['created_by' => $owner->id, 'is_verified' => true]);
        $company->assignModerator($owner, 'owner');

        return Auction::create([
            'number' => Auction::generateNumber(),
            'title' => 'Тестовый аукцион',
            'description' => 'Описание',
            'company_id' => $company->id,
            'created_by' => $owner->id,
            'type' => 'open',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(7),
            'trading_start' => now()->addDays(8),
            'starting_price' => 1000000,
            'step_percent' => 2.5,
            'status' => 'active',
        ]);
    }

    public function test_182_unverified_company_cannot_submit_rfq_bid(): void
    {
        $rfq = $this->openRfq();

        $bidder = $this->verifiedUser();
        $unverified = Company::factory()->create(['created_by' => $bidder->id, 'is_verified' => false]);
        $unverified->assignModerator($bidder, 'owner');

        $this->actingAs($bidder)
            ->post(route('rfqs.bids.store', $rfq), [
                'company_id' => $unverified->id,
                'price' => 100000,
                'deadline' => 30,
                'advance_percent' => 20,
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('rfq_bids', [
            'rfq_id' => $rfq->id,
            'company_id' => $unverified->id,
        ]);
    }

    public function test_182_unverified_company_not_offered_in_rfq_form(): void
    {
        $rfq = $this->openRfq();

        $bidder = $this->verifiedUser();
        $unverified = Company::factory()->create(['created_by' => $bidder->id, 'is_verified' => false]);
        $unverified->assignModerator($bidder, 'owner');

        $this->actingAs($bidder)
            ->get(route('rfqs.show', $rfq))
            ->assertViewHas('availableCompanies', fn ($companies) => $companies->isEmpty());
    }

    public function test_182_unverified_company_cannot_submit_auction_bid(): void
    {
        $auction = $this->openAuction();

        $bidder = $this->verifiedUser();
        $unverified = Company::factory()->create(['created_by' => $bidder->id, 'is_verified' => false]);
        $unverified->assignModerator($bidder, 'owner');

        $this->actingAs($bidder)
            ->post(route('auctions.bids.store', $auction), [
                'company_id' => $unverified->id,
                'acknowledgement' => true,
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('auction_bids', [
            'auction_id' => $auction->id,
            'company_id' => $unverified->id,
        ]);
    }

    public function test_182_verified_company_can_still_submit_rfq_bid(): void
    {
        $rfq = $this->openRfq();

        $bidder = $this->verifiedUser();
        $verified = Company::factory()->create(['created_by' => $bidder->id, 'is_verified' => true]);
        $verified->assignModerator($bidder, 'owner');

        $this->actingAs($bidder)
            ->post(route('rfqs.bids.store', $rfq), [
                'company_id' => $verified->id,
                'price' => 100000,
                'deadline' => 30,
                'advance_percent' => 20,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('rfq_bids', [
            'rfq_id' => $rfq->id,
            'company_id' => $verified->id,
        ]);
    }

    // #145 (правка): в составе компании показывается имя + фамилия
    public function test_145_company_members_show_full_name(): void
    {
        $owner = $this->verifiedUser();
        $company = Company::factory()->create(['created_by' => $owner->id, 'is_verified' => true]);
        $company->assignModerator($owner, 'owner');
        $member = User::factory()->create(['name' => 'Пётр', 'last_name' => 'Сидоров']);
        $company->assignModerator($member, 'member', $owner);

        $this->actingAs($owner)
            ->get(route('companies.show', $company))
            ->assertStatus(200)
            ->assertSee('Пётр Сидоров');
    }
}
