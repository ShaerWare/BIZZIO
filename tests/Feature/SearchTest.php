<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Company;
use App\Models\Project;
use App\Models\Industry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an industry for companies
        Industry::create(['name' => 'Test Industry', 'slug' => 'test-industry']);
    }

    /**
     * Test search page loads successfully
     */
    public function test_search_page_loads(): void
    {
        $response = $this->get(route('search.index'));

        $response->assertStatus(200);
        $response->assertViewIs('search.index');
    }

    /**
     * Test search with query parameter
     */
    public function test_search_with_query(): void
    {
        $response = $this->get(route('search.index', ['q' => 'test']));

        $response->assertStatus(200);
        $response->assertViewHas('query', 'test');
    }

    /**
     * Test search with type filter
     */
    public function test_search_with_type_filter(): void
    {
        $response = $this->get(route('search.index', ['q' => 'test', 'type' => 'companies']));

        $response->assertStatus(200);
        $response->assertViewHas('type', 'companies');
    }

    /**
     * Test quick search endpoint returns JSON
     */
    public function test_quick_search_returns_json(): void
    {
        $user = User::factory()->create();
        $industry = Industry::first();

        Company::create([
            'name' => 'Quick Search Company',
            'inn' => '1234567890',
            'industry_id' => $industry->id,
            'created_by' => $user->id,
            'is_verified' => true,
        ]);

        $response = $this->getJson(route('search.quick', ['q' => 'Quick']));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => ['type', 'id', 'title'],
        ]);
    }

    /**
     * Test quick search with short query returns empty results
     */
    public function test_quick_search_with_short_query(): void
    {
        $response = $this->getJson(route('search.quick', ['q' => 'a']));

        $response->assertStatus(200);
        $response->assertJson([]);
    }

    /**
     * Test quick search finds companies
     */
    public function test_quick_search_finds_companies(): void
    {
        $user = User::factory()->create();
        $industry = Industry::first();

        Company::create([
            'name' => 'Findable Company Test',
            'inn' => '9876543210',
            'industry_id' => $industry->id,
            'created_by' => $user->id,
            'is_verified' => true,
        ]);

        $response = $this->getJson(route('search.quick', ['q' => 'Findable']));

        $response->assertStatus(200);
        $response->assertJsonFragment(['title' => 'Findable Company Test']);
    }

    /**
     * Test quick search results contain required fields
     */
    public function test_quick_search_results_structure(): void
    {
        $user = User::factory()->create();
        $industry = Industry::first();

        Company::create([
            'name' => 'Structure Test Company',
            'inn' => '9876543210',
            'industry_id' => $industry->id,
            'created_by' => $user->id,
            'is_verified' => true,
        ]);

        $response = $this->getJson(route('search.quick', ['q' => 'Structure']));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => [
                'type',
                'type_label',
                'id',
                'title',
                'url',
            ],
        ]);
    }

    /**
     * Test quick search finds projects
     */
    public function test_quick_search_finds_projects(): void
    {
        $user = User::factory()->create();
        $industry = Industry::first();

        $company = Company::create([
            'name' => 'Owner Company',
            'inn' => '1234567890',
            'industry_id' => $industry->id,
            'created_by' => $user->id,
            'is_verified' => true,
        ]);

        Project::create([
            'name' => 'Searchable Project',
            'description' => 'Test project',
            'company_id' => $company->id,
            'created_by' => $user->id,
            'start_date' => now(),
            'status' => 'active',
        ]);

        $response = $this->getJson(route('search.quick', ['q' => 'Searchable']));

        $response->assertStatus(200);
        $response->assertJsonFragment(['title' => 'Searchable Project']);
    }

    /**
     * Test search routes exist
     */
    public function test_search_routes_exist(): void
    {
        $this->assertTrue(route('search.index') !== null);
        $this->assertTrue(route('search.quick') !== null);
    }
}

