<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\News;
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
}
