<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * #288 — Пользователь отображается связкой «Имя Фамилия» везде, где раньше выводилось
 * одно имя: быстрый поиск (в том числе при добавлении в компанию), карточки, уведомления.
 */
class UserFullNameDisplayTest extends TestCase
{
    use RefreshDatabase;

    private function person(string $name, string $lastName): User
    {
        return User::factory()->create([
            'name' => $name,
            'last_name' => $lastName,
            'email_verified_at' => now(),
        ]);
    }

    public function test_quick_search_returns_full_name(): void
    {
        $viewer = $this->person('Пётр', 'Смирнов');
        $this->person('Иван', 'Кузнецов');

        $response = $this->actingAs($viewer)->getJson('/search/quick?q=Иван');

        $response->assertOk();
        $response->assertJsonFragment(['title' => 'Иван Кузнецов']);
    }

    public function test_user_can_be_found_by_last_name(): void
    {
        $viewer = $this->person('Пётр', 'Смирнов');
        $this->person('Иван', 'Кузнецов');

        $response = $this->actingAs($viewer)->getJson('/search/quick?q=Кузнецов');

        $response->assertOk();
        $response->assertJsonFragment(['title' => 'Иван Кузнецов']);
    }

    public function test_search_page_shows_full_name(): void
    {
        $viewer = $this->person('Пётр', 'Смирнов');
        $this->person('Иван', 'Кузнецов');

        $response = $this->actingAs($viewer)->get('/search?q=Кузнецов&type=users');

        $response->assertOk();
        $response->assertSee('Иван Кузнецов');
    }

    public function test_public_profile_shows_full_name(): void
    {
        $viewer = $this->person('Пётр', 'Смирнов');
        $user = $this->person('Иван', 'Кузнецов');

        $response = $this->actingAs($viewer)->get(route('users.show', $user));

        $response->assertOk();
        $response->assertSee('Иван Кузнецов');
    }

    public function test_initials_use_first_and_last_name(): void
    {
        $user = $this->person('Иван', 'Кузнецов');

        $this->assertSame('ИК', $user->initials);
    }

    public function test_initials_fall_back_to_name_when_last_name_is_empty(): void
    {
        $user = User::factory()->create(['name' => 'Иван', 'last_name' => null]);

        $this->assertSame('ИВ', $user->initials);
    }

    public function test_company_page_shows_full_name_of_creator(): void
    {
        $user = $this->person('Иван', 'Кузнецов');
        $company = Company::factory()->create(['created_by' => $user->id, 'is_verified' => true]);
        $company->assignModerator($user, 'owner');

        $response = $this->actingAs($user)->get(route('companies.show', $company));

        $response->assertOk();
        $response->assertSee('Иван Кузнецов');
    }

    public function test_searchable_array_contains_last_name(): void
    {
        $user = $this->person('Иван', 'Кузнецов');

        $this->assertSame('Кузнецов', $user->toSearchableArray()['last_name']);
    }
}
