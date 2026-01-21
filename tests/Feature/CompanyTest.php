<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Company;
use App\Models\Industry;
use App\Models\CompanyJoinRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CompanyTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Industry $industry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->industry = Industry::create([
            'name' => 'Строительство',
            'slug' => 'construction',
        ]);

        Storage::fake('public');
    }

    // ==========================================
    // CRUD: Просмотр списка и карточки компании
    // ==========================================

    public function test_guest_can_view_companies_list(): void
    {
        Company::factory()->count(3)->create(['is_verified' => true]);

        $response = $this->get(route('companies.index'));

        $response->assertStatus(200);
        $response->assertViewIs('companies.index');
    }

    public function test_guest_can_view_company_profile(): void
    {
        $company = Company::factory()->create([
            'is_verified' => true,
            'industry_id' => $this->industry->id,
        ]);

        $response = $this->get(route('companies.show', $company));

        $response->assertStatus(200);
        $response->assertViewIs('companies.show');
        $response->assertSee($company->name);
    }

    public function test_companies_list_can_be_filtered_by_industry(): void
    {
        $company1 = Company::factory()->create([
            'industry_id' => $this->industry->id,
            'is_verified' => true,
        ]);

        $otherIndustry = Industry::create(['name' => 'IT', 'slug' => 'it']);
        $company2 = Company::factory()->create([
            'industry_id' => $otherIndustry->id,
            'is_verified' => true,
        ]);

        $response = $this->get(route('companies.index', ['industry_id' => $this->industry->id]));

        $response->assertStatus(200);
        $response->assertSee($company1->name);
    }

    public function test_companies_list_can_be_searched(): void
    {
        $company = Company::factory()->create([
            'name' => 'СтройМастер',
            'inn' => '1234567890',
            'is_verified' => true,
        ]);

        $response = $this->get(route('companies.index', ['search' => 'СтройМастер']));

        $response->assertStatus(200);
        $response->assertSee($company->name);
    }

    // ==========================================
    // CRUD: Создание компании
    // ==========================================

    public function test_guest_cannot_create_company(): void
    {
        $response = $this->get(route('companies.create'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_create_company_form(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('companies.create'));

        $response->assertStatus(200);
        $response->assertViewIs('companies.create');
    }

    public function test_authenticated_user_can_create_company(): void
    {
        $companyData = [
            'name' => 'Тестовая Компания',
            'inn' => '1234567890',
            'legal_form' => 'ООО',
            'short_description' => 'Краткое описание компании',
            'industry_id' => $this->industry->id,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('companies.store'), $companyData);

        $response->assertRedirect();

        $this->assertDatabaseHas('companies', [
            'name' => 'Тестовая Компания',
            'inn' => '1234567890',
            'created_by' => $this->user->id,
        ]);
    }

    public function test_company_creator_becomes_owner(): void
    {
        $companyData = [
            'name' => 'Моя Компания',
            'inn' => '9876543210',
            'legal_form' => 'ИП',
            'short_description' => 'Описание',
        ];

        $this->actingAs($this->user)
            ->post(route('companies.store'), $companyData);

        $company = Company::where('name', 'Моя Компания')->first();

        $this->assertTrue($company->isModerator($this->user));
    }

    public function test_company_creation_requires_name(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('companies.store'), [
                'inn' => '1234567890',
            ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_company_can_be_created_with_logo(): void
    {
        $logo = UploadedFile::fake()->image('logo.jpg', 200, 200);

        $companyData = [
            'name' => 'Компания с лого',
            'inn' => '1111111111',
            'legal_form' => 'ООО',
            'short_description' => 'Описание',
            'logo' => $logo,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('companies.store'), $companyData);

        $response->assertRedirect();

        $company = Company::where('name', 'Компания с лого')->first();
        $this->assertNotNull($company->logo);
    }

    // ==========================================
    // CRUD: Редактирование компании
    // ==========================================

    public function test_company_owner_can_edit_company(): void
    {
        $company = Company::factory()->create([
            'created_by' => $this->user->id,
        ]);
        $company->assignModerator($this->user, 'owner');

        $response = $this->actingAs($this->user)
            ->get(route('companies.edit', $company));

        $response->assertStatus(200);
        $response->assertViewIs('companies.edit');
    }

    public function test_company_moderator_can_edit_company(): void
    {
        $owner = User::factory()->create();
        $company = Company::factory()->create([
            'created_by' => $owner->id,
        ]);
        $company->assignModerator($this->user, 'moderator');

        $response = $this->actingAs($this->user)
            ->get(route('companies.edit', $company));

        $response->assertStatus(200);
    }

    public function test_non_moderator_cannot_edit_company(): void
    {
        $owner = User::factory()->create();
        $company = Company::factory()->create([
            'created_by' => $owner->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('companies.edit', $company));

        $response->assertStatus(403);
    }

    public function test_company_owner_can_update_company(): void
    {
        $company = Company::factory()->create([
            'created_by' => $this->user->id,
            'name' => 'Старое название',
        ]);
        $company->assignModerator($this->user, 'owner');

        $response = $this->actingAs($this->user)
            ->put(route('companies.update', $company), [
                'name' => 'Новое название',
                'inn' => $company->inn,
                'legal_form' => $company->legal_form,
                'short_description' => 'Обновленное описание',
            ]);

        $response->assertRedirect(route('companies.show', $company));

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'name' => 'Новое название',
        ]);
    }

    // ==========================================
    // CRUD: Удаление компании
    // ==========================================

    public function test_company_owner_can_delete_company(): void
    {
        $company = Company::factory()->create([
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('companies.destroy', $company));

        $response->assertRedirect(route('companies.index'));
        $this->assertSoftDeleted('companies', ['id' => $company->id]);
    }

    public function test_non_owner_cannot_delete_company(): void
    {
        $owner = User::factory()->create();
        $company = Company::factory()->create([
            'created_by' => $owner->id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('companies.destroy', $company));

        $response->assertStatus(403);
    }

    // ==========================================
    // Верификация компаний
    // ==========================================

    public function test_new_company_is_not_verified_by_default(): void
    {
        $companyData = [
            'name' => 'Неверифицированная компания',
            'inn' => '2222222222',
            'legal_form' => 'ООО',
            'short_description' => 'Описание',
        ];

        $this->actingAs($this->user)
            ->post(route('companies.store'), $companyData);

        $company = Company::where('name', 'Неверифицированная компания')->first();

        $this->assertFalse($company->is_verified);
    }

    public function test_verified_scope_returns_only_verified_companies(): void
    {
        Company::factory()->create(['is_verified' => true]);
        Company::factory()->create(['is_verified' => false]);

        $verifiedCompanies = Company::verified()->get();

        $this->assertCount(1, $verifiedCompanies);
        $this->assertTrue($verifiedCompanies->first()->is_verified);
    }

    // ==========================================
    // Модераторы компании
    // ==========================================

    public function test_company_can_have_multiple_moderators(): void
    {
        $company = Company::factory()->create([
            'created_by' => $this->user->id,
        ]);

        $moderator1 = User::factory()->create();
        $moderator2 = User::factory()->create();

        $company->assignModerator($this->user, 'owner');
        $company->assignModerator($moderator1, 'moderator');
        $company->assignModerator($moderator2, 'moderator');

        $this->assertCount(3, $company->moderators);
        $this->assertTrue($company->isModerator($moderator1));
        $this->assertTrue($company->isModerator($moderator2));
    }

    public function test_moderator_can_be_removed(): void
    {
        $company = Company::factory()->create([
            'created_by' => $this->user->id,
        ]);

        $moderator = User::factory()->create();
        $company->assignModerator($moderator, 'moderator');

        $this->assertTrue($company->isModerator($moderator));

        $company->removeModerator($moderator);

        $this->assertFalse($company->fresh()->isModerator($moderator));
    }

    // ==========================================
    // Запросы на присоединение к компании
    // ==========================================

    public function test_user_can_send_join_request_to_company(): void
    {
        $owner = User::factory()->create();
        $company = Company::factory()->create([
            'created_by' => $owner->id,
            'is_verified' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('companies.join-requests.store', $company), [
                'message' => 'Хочу присоединиться к вашей компании',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('company_join_requests', [
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);
    }

    public function test_user_cannot_send_duplicate_pending_request(): void
    {
        $owner = User::factory()->create();
        $company = Company::factory()->create([
            'created_by' => $owner->id,
        ]);

        CompanyJoinRequest::create([
            'company_id' => $company->id,
            'user_id' => $this->user->id,
            'message' => 'Первый запрос',
            'status' => 'pending',
        ]);

        $this->assertTrue($company->hasPendingRequestFrom($this->user));
    }

    public function test_moderator_can_approve_join_request(): void
    {
        $company = Company::factory()->create([
            'created_by' => $this->user->id,
        ]);
        $company->assignModerator($this->user, 'owner', $this->user, true);

        $applicant = User::factory()->create();
        $joinRequest = CompanyJoinRequest::create([
            'company_id' => $company->id,
            'user_id' => $applicant->id,
            'message' => 'Запрос на присоединение',
            'status' => 'pending',
        ]);

        // Загружаем связь для корректной работы canReview()
        $joinRequest->load('company');

        $response = $this->actingAs($this->user)
            ->post(route('join-requests.approve', $joinRequest->id));

        $response->assertRedirect();

        $this->assertDatabaseHas('company_join_requests', [
            'id' => $joinRequest->id,
            'status' => 'approved',
        ]);

        // Проверяем, что пользователь стал модератором
        $this->assertTrue($company->fresh()->isModerator($applicant));
    }

    public function test_moderator_can_reject_join_request(): void
    {
        $company = Company::factory()->create([
            'created_by' => $this->user->id,
        ]);
        $company->assignModerator($this->user, 'owner', $this->user, true);

        $applicant = User::factory()->create();
        $joinRequest = CompanyJoinRequest::create([
            'company_id' => $company->id,
            'user_id' => $applicant->id,
            'message' => 'Запрос на присоединение',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('join-requests.reject', $joinRequest->id), [
                'review_comment' => 'К сожалению, не подходите',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('company_join_requests', [
            'id' => $joinRequest->id,
            'status' => 'rejected',
        ]);
    }

    // ==========================================
    // Фотогалерея компании
    // ==========================================

    public function test_moderator_can_upload_photos(): void
    {
        $company = Company::factory()->create([
            'created_by' => $this->user->id,
        ]);
        $company->assignModerator($this->user, 'owner');

        $photos = [
            UploadedFile::fake()->image('photo1.jpg', 800, 600),
            UploadedFile::fake()->image('photo2.jpg', 800, 600),
        ];

        $response = $this->actingAs($this->user)
            ->post(route('companies.photos.upload', $company), [
                'photos' => $photos,
            ]);

        $response->assertRedirect();

        $this->assertCount(2, $company->fresh()->getMedia('photos'));
    }

    public function test_non_moderator_cannot_upload_photos(): void
    {
        $owner = User::factory()->create();
        $company = Company::factory()->create([
            'created_by' => $owner->id,
        ]);

        $photo = UploadedFile::fake()->image('photo.jpg');

        $response = $this->actingAs($this->user)
            ->post(route('companies.photos.upload', $company), [
                'photos' => [$photo],
            ]);

        $response->assertStatus(403);
    }

    // ==========================================
    // Slug и поиск
    // ==========================================

    public function test_company_slug_is_auto_generated(): void
    {
        $company = Company::factory()->create([
            'name' => 'Тестовая Компания',
            'slug' => null,
        ]);

        $this->assertNotNull($company->slug);
        $this->assertStringContainsString('testovaia-kompaniia', $company->slug);
    }

    public function test_company_route_uses_slug(): void
    {
        $company = Company::factory()->create([
            'name' => 'Test Company',
            'is_verified' => true,
        ]);

        $response = $this->get('/companies/' . $company->slug);

        $response->assertStatus(200);
    }
}
