<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * #187 Права на редактирование профиля компании.
 *
 * Кнопка «Редактировать» была закрыта проверкой `canManageModerators()` — её видели только
 * владелец и админ, а модератор компании нет. При этом бэкенд опирался на `isModerator()`,
 * которая истинна для ЛЮБОЙ записи в company_user, включая рядового «Участника» (member,
 * роль по умолчанию с #144): участник мог править профиль и файлы по прямой ссылке.
 */
class CompanyProfileEditRightsTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['email_verified_at' => now()]);
        $this->company = Company::factory()->create([
            'created_by' => $this->owner->id,
            'is_verified' => true,
        ]);
        $this->company->assignModerator($this->owner, 'owner');

        Storage::fake('public');
    }

    private function memberWithRole(string $role): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->company->assignModerator($user, $role, $this->owner);

        return $user;
    }

    // ==========================================================
    // Модератор получает доступ (суть задачи)
    // ==========================================================

    public function test_moderator_sees_edit_button(): void
    {
        $moderator = $this->memberWithRole('moderator');

        $this->actingAs($moderator)
            ->get(route('companies.show', $this->company))
            ->assertOk()
            ->assertSee(route('companies.edit', $this->company), false);
    }

    public function test_moderator_can_open_edit_form(): void
    {
        $moderator = $this->memberWithRole('moderator');

        $this->actingAs($moderator)
            ->get(route('companies.edit', $this->company))
            ->assertOk()
            ->assertViewIs('companies.edit');
    }

    public function test_moderator_can_update_company_profile(): void
    {
        $moderator = $this->memberWithRole('moderator');

        $this->actingAs($moderator)
            ->put(route('companies.update', $this->company), [
                'name' => 'Обновлённое название',
                'short_description' => 'Новое описание',
            ])
            ->assertRedirect();

        $this->assertSame('Обновлённое название', $this->company->fresh()->name);
    }

    public function test_moderator_can_upload_and_delete_photo(): void
    {
        $moderator = $this->memberWithRole('moderator');

        $this->actingAs($moderator)
            ->post(route('companies.photos.upload', $this->company), [
                'photos' => [UploadedFile::fake()->image('office.jpg')],
            ])
            ->assertRedirect();

        $photo = $this->company->fresh()->getMedia('photos')->first();
        $this->assertNotNull($photo);

        $this->actingAs($moderator)
            ->delete(route('companies.photos.delete', [$this->company, $photo->id]))
            ->assertRedirect();

        $this->assertCount(0, $this->company->fresh()->getMedia('photos'));
    }

    public function test_admin_role_can_edit_profile(): void
    {
        $admin = $this->memberWithRole('admin');

        $this->actingAs($admin)
            ->get(route('companies.edit', $this->company))
            ->assertOk();
    }

    public function test_owner_can_edit_profile(): void
    {
        $this->actingAs($this->owner)
            ->get(route('companies.edit', $this->company))
            ->assertOk();
    }

    // ==========================================================
    // Рядовой участник доступа не получает
    // ==========================================================

    public function test_plain_member_cannot_open_edit_form(): void
    {
        $member = $this->memberWithRole('member');

        $this->actingAs($member)
            ->get(route('companies.edit', $this->company))
            ->assertForbidden();
    }

    public function test_plain_member_cannot_update_company_profile(): void
    {
        $member = $this->memberWithRole('member');

        $this->actingAs($member)
            ->put(route('companies.update', $this->company), ['name' => 'Захвачено'])
            ->assertForbidden();

        $this->assertNotSame('Захвачено', $this->company->fresh()->name);
    }

    public function test_plain_member_cannot_upload_photos(): void
    {
        $member = $this->memberWithRole('member');

        $this->actingAs($member)
            ->post(route('companies.photos.upload', $this->company), [
                'photos' => [UploadedFile::fake()->image('office.jpg')],
            ])
            ->assertForbidden();
    }

    public function test_plain_member_does_not_see_edit_button(): void
    {
        $member = $this->memberWithRole('member');

        $this->actingAs($member)
            ->get(route('companies.show', $this->company))
            ->assertOk()
            ->assertDontSee(route('companies.edit', $this->company), false);
    }

    public function test_outsider_cannot_edit_profile(): void
    {
        $outsider = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($outsider)
            ->get(route('companies.edit', $this->company))
            ->assertForbidden();
    }
}
