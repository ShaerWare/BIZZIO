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
 * #176 Документы компании: поочерёдная загрузка нескольких PDF, выборочное удаление
 * и ограничение суммарного объёма (10 МБ) с учётом уже загруженных файлов.
 */
class CompanyDocumentsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $this->company = Company::factory()->create(['created_by' => $this->user->id]);
        $this->company->assignModerator($this->user, 'owner');
    }

    /**
     * Настоящий (по сигнатуре) PDF заданного размера: media library определяет mime
     * по содержимому, а UploadedFile::fake()->create() отдаёт пустой файл.
     */
    private function pdf(string $name, int $kilobytes): UploadedFile
    {
        $header = '%PDF-1.4'."\n";
        $content = $header.str_repeat('0', max(0, $kilobytes * 1024 - strlen($header)));

        return UploadedFile::fake()->createWithContent($name, $content);
    }

    /** @return array<string, mixed> */
    private function updatePayload(array $overrides = []): array
    {
        return array_merge([
            'name' => $this->company->name,
            'inn' => $this->company->inn,
        ], $overrides);
    }

    public function test_several_documents_are_uploaded_at_once(): void
    {
        $this->actingAs($this->user)
            ->put(route('companies.update', $this->company), $this->updatePayload([
                'documents' => [$this->pdf('ustav.pdf', 500), $this->pdf('inn.pdf', 500)],
            ]))
            ->assertSessionHasNoErrors();

        $this->assertCount(2, $this->company->fresh()->getMedia('documents'));
    }

    public function test_documents_are_appended_not_replaced(): void
    {
        $this->actingAs($this->user)->put(route('companies.update', $this->company), $this->updatePayload([
            'documents' => [$this->pdf('ustav.pdf', 300)],
        ]))->assertSessionHasNoErrors();

        $this->actingAs($this->user)->put(route('companies.update', $this->company), $this->updatePayload([
            'documents' => [$this->pdf('ogrn.pdf', 300)],
        ]))->assertSessionHasNoErrors();

        $this->assertCount(2, $this->company->fresh()->getMedia('documents'));
    }

    public function test_single_document_can_be_deleted(): void
    {
        $this->actingAs($this->user)->put(route('companies.update', $this->company), $this->updatePayload([
            'documents' => [$this->pdf('ustav.pdf', 300), $this->pdf('inn.pdf', 300)],
        ]))->assertSessionHasNoErrors();

        $company = $this->company->fresh();
        $media = $company->getMedia('documents')->first();

        $this->actingAs($this->user)
            ->deleteJson(route('companies.documents.delete', [$company, $media->id]))
            ->assertOk();

        $remaining = $company->fresh()->getMedia('documents');
        $this->assertCount(1, $remaining);
        $this->assertNotEquals($media->id, $remaining->first()->id);
    }

    public function test_total_size_over_ten_megabytes_is_rejected(): void
    {
        $this->actingAs($this->user)
            ->put(route('companies.update', $this->company), $this->updatePayload([
                'documents' => [$this->pdf('a.pdf', 6000), $this->pdf('b.pdf', 6000)],
            ]))
            ->assertSessionHasErrors('documents');

        $this->assertCount(0, $this->company->fresh()->getMedia('documents'));
    }

    public function test_already_uploaded_files_count_towards_the_limit(): void
    {
        $this->actingAs($this->user)->put(route('companies.update', $this->company), $this->updatePayload([
            'documents' => [$this->pdf('big.pdf', 8000)],
        ]))->assertSessionHasNoErrors();

        $this->actingAs($this->user)
            ->put(route('companies.update', $this->company), $this->updatePayload([
                'documents' => [$this->pdf('more.pdf', 4000)],
            ]))
            ->assertSessionHasErrors('documents');

        $this->assertCount(1, $this->company->fresh()->getMedia('documents'));
    }

    public function test_non_pdf_document_is_rejected(): void
    {
        $this->actingAs($this->user)
            ->put(route('companies.update', $this->company), $this->updatePayload([
                'documents' => [UploadedFile::fake()->create('scan.jpg', 100, 'image/jpeg')],
            ]))
            ->assertSessionHasErrors('documents.0');
    }
}
