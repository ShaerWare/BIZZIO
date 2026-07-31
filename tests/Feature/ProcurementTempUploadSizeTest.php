<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * #185 Лимит суммарного объёма конкурсной документации при temp-загрузке.
 */
class ProcurementTempUploadSizeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['email_verified_at' => now()]);
        Storage::fake('local');
    }

    private function upload(string $collection, int $kilobytes)
    {
        $pdf = UploadedFile::fake()->create('doc.pdf', $kilobytes, 'application/pdf');

        return $this->actingAs($this->user)->postJson(route('procurement-temp-upload.store'), [
            'file' => $pdf,
            'collection' => $collection,
        ]);
    }

    public function test_reupload_single_collection_replaces_and_does_not_double_count(): void
    {
        // Повторная загрузка одиночной коллекции ЗАМЕНЯЕТ файл — прежний размер не должен
        // учитываться в сумме (иначе перезагрузка того же ~12 МБ файла ложно упиралась в 20 МБ).
        $this->upload('technical_specification', 12000)->assertOk()->assertJsonPath('success', true);
        $this->upload('technical_specification', 12000)->assertOk()->assertJsonPath('success', true);
    }

    public function test_other_documents_still_enforce_cumulative_limit(): void
    {
        // «Прочие файлы» накапливаются — суммарный лимит 20 МБ по-прежнему действует.
        $this->upload('other_documents', 12000)->assertOk()->assertJsonPath('success', true);
        $this->upload('other_documents', 12000)
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_total_across_different_single_collections_still_limited(): void
    {
        // Разные одиночные коллекции суммируются: 12 + 12 > 20 → второй отклоняется.
        $this->upload('technical_specification', 12000)->assertOk()->assertJsonPath('success', true);
        $this->upload('notice', 12000)
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }
}
