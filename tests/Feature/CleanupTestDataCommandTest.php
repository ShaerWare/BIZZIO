<?php

namespace Tests\Feature;

use App\Models\Auction;
use App\Models\AuctionBid;
use App\Models\Company;
use App\Models\Project;
use App\Models\Rfq;
use App\Models\RfqBid;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CleanupTestDataCommandTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $this->company = Company::factory()->create([
            'created_by' => $this->user->id,
            'is_verified' => true,
        ]);
        $this->company->assignModerator($this->user, 'owner');
    }

    protected function createRfq(array $attributes = []): Rfq
    {
        return Rfq::create(array_merge([
            'number' => Rfq::generateNumber(),
            'title' => 'Тестовый RFQ',
            'description' => 'Описание',
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'type' => 'open',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(7),
            'status' => 'active',
        ], $attributes));
    }

    protected function createAuction(array $attributes = []): Auction
    {
        return Auction::create(array_merge([
            'number' => Auction::generateNumber(),
            'title' => 'Тестовый аукцион',
            'description' => 'Описание',
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'type' => 'open',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(7),
            'trading_start' => now()->addDays(8),
            'starting_price' => 1000000,
            'step_percent' => 2.5,
            'status' => 'active',
        ], $attributes));
    }

    public function test_cleanup_command_deletes_company_and_cascaded_data(): void
    {
        $rfq = $this->createRfq();
        $auction = $this->createAuction();

        $bidder = User::factory()->create(['email_verified_at' => now()]);
        $bidderCompany = Company::factory()->create(['created_by' => $bidder->id]);

        RfqBid::create([
            'rfq_id' => $rfq->id,
            'company_id' => $bidderCompany->id,
            'user_id' => $bidder->id,
            'price' => 500000,
            'deadline' => 30,
            'status' => 'pending',
        ]);

        AuctionBid::create([
            'auction_id' => $auction->id,
            'company_id' => $bidderCompany->id,
            'user_id' => $bidder->id,
            'price' => 900000,
            'type' => 'initial',
            'status' => 'pending',
        ]);

        DB::table('rfq_invitations')->insert([
            'rfq_id' => $rfq->id,
            'company_id' => $bidderCompany->id,
            'invited_by' => $this->user->id,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('auction_invitations')->insert([
            'auction_id' => $auction->id,
            'company_id' => $bidderCompany->id,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $project = Project::create([
            'name' => 'Тестовый проект',
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'status' => 'active',
        ]);

        // Run command with --force and specific company ID
        $this->artisan('cleanup:test-data', ['--force' => true])
            ->assertExitCode(0);

        // Verify soft-deletes
        $this->assertSoftDeleted('companies', ['id' => $this->company->id]);
        $this->assertSoftDeleted('rfqs', ['id' => $rfq->id]);
        $this->assertSoftDeleted('auctions', ['id' => $auction->id]);
        $this->assertSoftDeleted('rfq_bids', ['rfq_id' => $rfq->id]);
        $this->assertSoftDeleted('auction_bids', ['auction_id' => $auction->id]);
        $this->assertSoftDeleted('projects', ['id' => $project->id]);

        // Verify hard-deletes
        $this->assertDatabaseMissing('rfq_invitations', ['rfq_id' => $rfq->id]);
        $this->assertDatabaseMissing('auction_invitations', ['auction_id' => $auction->id]);
        $this->assertDatabaseMissing('company_user', ['company_id' => $this->company->id]);

        // Verify bidder company is also soft-deleted (--force = all)
        $this->assertSoftDeleted('companies', ['id' => $bidderCompany->id]);
    }

    public function test_cleanup_command_with_force_skips_confirmation(): void
    {
        $this->artisan('cleanup:test-data', ['--force' => true])
            ->assertExitCode(0);

        $this->assertSoftDeleted('companies', ['id' => $this->company->id]);
    }

    public function test_cleanup_command_shows_empty_message_when_no_companies(): void
    {
        // Delete all companies first
        Company::query()->forceDelete();
        DB::table('company_user')->delete();

        $this->artisan('cleanup:test-data', ['--force' => true])
            ->expectsOutput('Компании не найдены.')
            ->assertExitCode(0);
    }
}
