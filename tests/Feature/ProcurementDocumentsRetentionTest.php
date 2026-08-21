<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Auction;
use App\Models\Company;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * #296 — Конкурсная документация доступна 30 дней после завершения этапа 2, затем удаляется.
 */
class ProcurementDocumentsRetentionTest extends TestCase
{
    use RefreshDatabase;

    private User $organizer;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->organizer = User::factory()->create(['email_verified_at' => now()]);
        $this->company = Company::factory()->create(['created_by' => $this->organizer->id, 'is_verified' => true]);
        $this->company->assignModerator($this->organizer, 'owner');
        Storage::fake('public');
        Queue::fake();
    }

    private function auction(string $status = 'trading'): Auction
    {
        return Auction::create([
            'number' => Auction::generateNumber(),
            'title' => 'Закупка на монтаж',
            'company_id' => $this->company->id,
            'created_by' => $this->organizer->id,
            'type' => 'open',
            'procedure' => Auction::PROCEDURE_COMMERCIAL,
            'start_date' => now()->subDays(3),
            'end_date' => now()->subDays(2),
            'trading_start' => now()->subDay(),
            'trading_end' => now()->addDay(),
            'starting_price' => 1_000_000,
            'weight_price' => 70, 'weight_deadline' => 20, 'weight_advance' => 10,
            'step_price' => 0.5, 'step_deadline' => 1, 'step_advance' => 5,
            'max_deadline' => 100, 'max_advance' => 50,
            'status' => $status,
        ]);
    }

    private function withDocument(Auction $auction): Auction
    {
        // Медиатека проверяет реальный mime-тип, поэтому файл должен начинаться с PDF-заголовка.
        $content = '%PDF-1.4'."\n".str_repeat('0', 1024);

        $auction->addMedia(UploadedFile::fake()->createWithContent('notice.pdf', $content))
            ->toMediaCollection('notice');

        return $auction->fresh();
    }

    public function test_closing_stamps_closed_at(): void
    {
        $auction = $this->auction();
        $this->assertNull($auction->closed_at);

        $auction->update(['status' => 'closed']);

        $this->assertNotNull($auction->fresh()->closed_at);
    }

    public function test_closed_at_is_not_shifted_by_later_updates(): void
    {
        $auction = $this->auction();
        $auction->update(['status' => 'closed']);
        $closedAt = $auction->fresh()->closed_at;

        $this->travel(2)->days();
        $auction->update(['title' => 'Другое название']);

        $this->assertEquals($closedAt->timestamp, $auction->fresh()->closed_at->timestamp);
    }

    public function test_documents_are_available_for_thirty_days_after_close(): void
    {
        $auction = $this->withDocument($this->auction());
        $auction->update(['status' => 'closed']);
        $auction = $auction->fresh();

        $this->assertSame(30, Setting::documentsRetentionDays());
        $this->assertTrue($auction->documentsAccessibleBy($this->organizer));
        $this->assertFalse($auction->documentsRetentionExpired());
        $this->assertEquals(
            $auction->closed_at->copy()->addDays(30)->toDateString(),
            $auction->documentsAvailableUntil()->toDateString()
        );
    }

    public function test_documents_are_closed_after_retention_window(): void
    {
        $auction = $this->withDocument($this->auction());
        $auction->update(['status' => 'closed']);

        $this->travel(31)->days();
        $auction = $auction->fresh();

        $this->assertTrue($auction->documentsRetentionExpired());
        $this->assertFalse($auction->documentsAccessibleBy($this->organizer));
    }

    public function test_download_is_forbidden_after_retention_window(): void
    {
        $auction = $this->withDocument($this->auction());
        $auction->update(['status' => 'closed']);

        $this->travel(31)->days();

        $this->actingAs($this->organizer)
            ->get(route('auctions.documents.archive', $auction->fresh()))
            ->assertForbidden();
    }

    public function test_download_works_inside_retention_window(): void
    {
        $auction = $this->withDocument($this->auction());
        $auction->update(['status' => 'closed']);

        $this->travel(29)->days();

        $this->actingAs($this->organizer)
            ->get(route('auctions.documents.archive', $auction->fresh()))
            ->assertOk();
    }

    public function test_cleanup_command_removes_expired_documents_only(): void
    {
        $expired = $this->withDocument($this->auction());
        $expired->update(['status' => 'closed', 'closed_at' => now()->subDays(31)]);

        $fresh = $this->withDocument($this->auction());
        $fresh->update(['status' => 'closed', 'closed_at' => now()->subDays(5)]);

        $this->artisan('documents:cleanup')->assertSuccessful();

        $this->assertCount(0, $expired->fresh()->getMedia('notice'));
        $this->assertCount(1, $fresh->fresh()->getMedia('notice'));
    }

    public function test_retention_setting_is_read_in_days(): void
    {
        Setting::put(Setting::DOCUMENTS_RETENTION_DAYS, 45);

        $this->assertSame(45, Setting::documentsRetentionDays());
    }

    public function test_legacy_month_setting_is_converted_to_days(): void
    {
        Setting::put(Setting::DOCUMENTS_RETENTION_MONTHS, 3);

        $this->assertSame(90, Setting::documentsRetentionDays());
    }
}
