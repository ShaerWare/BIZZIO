<?php

namespace Tests\Feature;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_returns_xml(): void
    {
        Company::factory()->create(['is_verified' => true]);

        $response = $this->get('/sitemap.xml');

        $response->assertStatus(200);
        $this->assertStringContainsString('application/xml', $response->headers->get('Content-Type'));
        $response->assertSee('<urlset', false);
        $response->assertSee(url('/companies'), false);
    }

    public function test_landing_has_seo_meta(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('og:title', false);
        $response->assertSee('application/ld+json', false);
        $response->assertSee('name="description"', false);
    }
}
