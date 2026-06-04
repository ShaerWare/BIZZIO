<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Project;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * #152: динамический sitemap.xml по публичным разделам.
     */
    public function index(): Response
    {
        $urls = [];

        // Статические публичные страницы
        $static = [
            ['path' => '/', 'priority' => '1.0', 'changefreq' => 'daily'],
            ['path' => '/companies', 'priority' => '0.8', 'changefreq' => 'daily'],
            ['path' => '/projects', 'priority' => '0.8', 'changefreq' => 'daily'],
            ['path' => '/tenders', 'priority' => '0.8', 'changefreq' => 'daily'],
            ['path' => '/news', 'priority' => '0.7', 'changefreq' => 'daily'],
        ];
        foreach ($static as $s) {
            $urls[] = ['loc' => url($s['path']), 'priority' => $s['priority'], 'changefreq' => $s['changefreq']];
        }

        // Верифицированные компании
        Company::verified()->latest('updated_at')->limit(5000)->get()->each(function (Company $company) use (&$urls) {
            $urls[] = [
                'loc' => route('companies.show', $company),
                'lastmod' => optional($company->updated_at)->toAtomString(),
                'priority' => '0.7',
                'changefreq' => 'weekly',
            ];
        });

        // Проекты
        Project::latest('updated_at')->limit(5000)->get()->each(function (Project $project) use (&$urls) {
            $urls[] = [
                'loc' => route('projects.show', $project->slug ?? $project->id),
                'lastmod' => optional($project->updated_at)->toAtomString(),
                'priority' => '0.6',
                'changefreq' => 'weekly',
            ];
        });

        return response()
            ->view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }
}
