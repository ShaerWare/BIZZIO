<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * #287 — Организационно-правовая форма стоит перед названием компании, а само название
 * принимается без ОПФ и кавычек: полное наименование собирается из двух полей.
 */
class CompanyLegalFormTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['email_verified_at' => now()]);
        Storage::fake('public');
    }

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Ромашка',
            'inn' => '7701234567',
            'legal_form' => 'ООО',
        ], $overrides);
    }

    public function test_legal_form_field_precedes_name_field_on_create_page(): void
    {
        $response = $this->actingAs($this->user)->get(route('companies.create'));

        $response->assertOk();

        $html = $response->getContent();
        $this->assertLessThan(
            strpos($html, 'name="name"'),
            strpos($html, 'name="legal_form"'),
            'Поле ОПФ должно идти перед полем названия компании'
        );
    }

    public function test_legal_form_field_precedes_name_field_on_edit_page(): void
    {
        $company = Company::factory()->create(['created_by' => $this->user->id]);
        $company->assignModerator($this->user, 'owner');

        $response = $this->actingAs($this->user)->get(route('companies.edit', $company));

        $response->assertOk();

        $html = $response->getContent();
        $this->assertLessThan(
            strpos($html, 'name="name"'),
            strpos($html, 'name="legal_form"'),
            'Поле ОПФ должно идти перед полем названия компании'
        );
    }

    public function test_company_is_created_with_clean_name(): void
    {
        $response = $this->actingAs($this->user)->post(route('companies.store'), $this->payload());

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('companies', ['name' => 'Ромашка', 'legal_form' => 'ООО']);
    }

    public function test_name_starting_with_legal_form_is_rejected(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('companies.store'), $this->payload(['name' => 'ООО Ромашка']));

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseCount('companies', 0);
    }

    public function test_name_with_quotes_is_rejected(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('companies.store'), $this->payload(['name' => '«Ромашка»']));

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseCount('companies', 0);
    }

    public function test_name_containing_legal_form_inside_is_allowed(): void
    {
        // «Ипотека» начинается с «Ип», но это часть слова — блокировать нельзя.
        $response = $this->actingAs($this->user)
            ->post(route('companies.store'), $this->payload(['name' => 'Ипотека Плюс']));

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('companies', ['name' => 'Ипотека Плюс']);
    }

    public function test_existing_name_with_legal_form_can_still_be_saved(): void
    {
        // Компании, заведённые до #287, хранят ОПФ прямо в названии — редактирование
        // остальных полей им блокировать нельзя.
        $company = Company::factory()->create([
            'created_by' => $this->user->id,
            'name' => 'ООО Ромашка',
        ]);
        $company->assignModerator($this->user, 'owner');

        $response = $this->actingAs($this->user)->put(route('companies.update', $company), [
            'name' => 'ООО Ромашка',
            'inn' => $company->inn,
            'short_description' => 'Новое описание',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('companies', ['id' => $company->id, 'short_description' => 'Новое описание']);
    }

    public function test_changing_name_to_one_with_legal_form_is_rejected(): void
    {
        $company = Company::factory()->create([
            'created_by' => $this->user->id,
            'name' => 'Ромашка',
        ]);
        $company->assignModerator($this->user, 'owner');

        $response = $this->actingAs($this->user)->put(route('companies.update', $company), [
            'name' => 'ООО Ромашка Плюс',
            'inn' => $company->inn,
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_legal_name_is_assembled_from_both_fields(): void
    {
        $company = Company::factory()->create([
            'created_by' => $this->user->id,
            'name' => 'Ромашка',
            'legal_form' => 'ООО',
            'inn' => '7701234567',
        ]);

        $this->assertSame('ООО «Ромашка» (ИНН 7701234567)', $company->legalNameWithInn());
    }
}
