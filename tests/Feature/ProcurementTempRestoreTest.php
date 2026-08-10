<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Rfq;
use App\Models\User;
use App\Support\ProcurementDocuments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * #185 Сохранение конкурсной документации при ошибке валидации формы:
 * после исправления ошибки процедура должна создаваться БЕЗ повторного прикрепления файлов.
 */
class ProcurementTempRestoreTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $this->company = Company::factory()->create(['created_by' => $this->user->id]);
        $this->company->assignModerator($this->user, 'owner');

        Storage::fake('public');
        Queue::fake();
    }

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'RFQ с восстановлением файлов',
            'description' => 'Описание',
            'company_id' => $this->company->id,
            'type' => 'open',
            'currency' => 'RUB',
            'start_date' => now()->format('Y-m-d H:i:s'),
            'end_date' => now()->addDays(7)->format('Y-m-d H:i:s'),
            'weight_price' => 40,
            'weight_deadline' => 30,
            'weight_advance' => 30,
            'status' => 'draft',
        ], $overrides);
    }

    private function uploadTemp(string $collection = 'technical_specification'): void
    {
        $this->actingAs($this->user)
            ->postJson(route('procurement-temp-upload.store'), [
                'file' => UploadedFile::fake()->createWithContent('tz.pdf', '%PDF-1.4 test content'),
                'collection' => $collection,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_temp_file_survives_validation_error_and_rfq_is_created_on_retry(): void
    {
        $this->uploadTemp();

        // 1-я отправка: ошибка в дате (end_date раньше start_date) + файл ТЗ уже в temp.
        $this->actingAs($this->user)
            ->from(route('rfqs.create'))
            ->post(route('rfqs.store'), $this->payload([
                'end_date' => now()->subDay()->format('Y-m-d H:i:s'),
            ]))
            ->assertSessionHasErrors('end_date')
            ->assertSessionDoesntHaveErrors('technical_specification');

        // Temp-файл должен уцелеть.
        $this->assertTrue(ProcurementDocuments::hasTemp('technical_specification'));

        // 2-я отправка: дата исправлена, файл заново НЕ прикладываем.
        $this->actingAs($this->user)
            ->from(route('rfqs.create'))
            ->post(route('rfqs.store'), $this->payload())
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $rfq = Rfq::where('title', 'RFQ с восстановлением файлов')->first();
        $this->assertNotNull($rfq);
        $this->assertTrue($rfq->hasMedia('technical_specification'));
    }

    public function test_temp_files_are_cleared_after_successful_creation(): void
    {
        $this->uploadTemp();

        $this->actingAs($this->user)
            ->post(route('rfqs.store'), $this->payload())
            ->assertRedirect();

        $this->assertFalse(ProcurementDocuments::hasTemp('technical_specification'));
    }

    public function test_fresh_create_form_does_not_reuse_documents_of_an_abandoned_procedure(): void
    {
        // Пользователь приложил файлы и ушёл с формы, не создав процедуру.
        $this->uploadTemp();

        // Повторное «чистое» открытие формы — файлов прошлой попытки быть не должно.
        $this->actingAs($this->user)->get(route('rfqs.create'))->assertOk();

        $this->assertFalse(ProcurementDocuments::hasTemp('technical_specification'));
    }

    public function test_create_form_after_validation_error_keeps_attached_documents(): void
    {
        $this->uploadTemp();

        $this->actingAs($this->user)
            ->from(route('rfqs.create'))
            ->post(route('rfqs.store'), $this->payload(['title' => '']))
            ->assertSessionHasErrors('title');

        // Возврат на форму по redirect back — файлы должны остаться прикреплёнными.
        $this->actingAs($this->user)->get(route('rfqs.create'))->assertOk();

        $this->assertTrue(ProcurementDocuments::hasTemp('technical_specification'));
    }
}
