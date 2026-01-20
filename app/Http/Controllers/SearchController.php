<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Company;
use App\Models\Project;
use App\Models\Rfq;
use App\Models\Auction;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SearchController extends Controller
{
    /**
     * Глобальный поиск по всем сущностям
     */
    public function index(Request $request)
    {
        $query = $request->get('q', '');
        $type = $request->get('type', 'all');

        if (strlen($query) < 2) {
            return view('search.index', [
                'query' => $query,
                'type' => $type,
                'results' => collect(),
                'counts' => $this->getEmptyCounts(),
            ]);
        }

        $results = $this->performSearch($query, $type);
        $counts = $this->getSearchCounts($query);

        return view('search.index', [
            'query' => $query,
            'type' => $type,
            'results' => $results,
            'counts' => $counts,
        ]);
    }

    /**
     * AJAX-поиск для быстрых результатов (dropdown в хедере)
     */
    public function quick(Request $request)
    {
        $query = $request->get('q', '');

        if (strlen($query) < 2) {
            return response()->json(['results' => []]);
        }

        $results = [];

        // Компании (максимум 3)
        $companies = Company::search($query)->take(3)->get();
        foreach ($companies as $company) {
            $results[] = [
                'type' => 'company',
                'type_label' => 'Компания',
                'id' => $company->id,
                'title' => $company->name,
                'subtitle' => $company->inn ? "ИНН: {$company->inn}" : null,
                'url' => route('companies.show', $company),
            ];
        }

        // Проекты (максимум 3)
        $projects = Project::search($query)->take(3)->get();
        foreach ($projects as $project) {
            $results[] = [
                'type' => 'project',
                'type_label' => 'Проект',
                'id' => $project->id,
                'title' => $project->name,
                'subtitle' => $project->company?->name,
                'url' => route('projects.show', $project->slug ?? $project->id),
            ];
        }

        // RFQ (максимум 2)
        $rfqs = Rfq::search($query)->take(2)->get();
        foreach ($rfqs as $rfq) {
            $results[] = [
                'type' => 'rfq',
                'type_label' => 'Запрос котировок',
                'id' => $rfq->id,
                'title' => $rfq->title,
                'subtitle' => $rfq->number,
                'url' => route('rfqs.show', $rfq),
            ];
        }

        // Аукционы (максимум 2)
        $auctions = Auction::search($query)->take(2)->get();
        foreach ($auctions as $auction) {
            $results[] = [
                'type' => 'auction',
                'type_label' => 'Аукцион',
                'id' => $auction->id,
                'title' => $auction->title,
                'subtitle' => $auction->number,
                'url' => route('auctions.show', $auction),
            ];
        }

        return response()->json([
            'results' => $results,
            'total' => count($results),
            'query' => $query,
        ]);
    }

    /**
     * Выполнить поиск по указанному типу
     */
    protected function performSearch(string $query, string $type): Collection
    {
        $results = collect();

        switch ($type) {
            case 'users':
                $results = User::search($query)->take(50)->get()->map(fn($item) => $this->formatUser($item));
                break;

            case 'companies':
                $results = Company::search($query)->take(50)->get()->map(fn($item) => $this->formatCompany($item));
                break;

            case 'projects':
                $results = Project::search($query)->take(50)->get()->map(fn($item) => $this->formatProject($item));
                break;

            case 'rfqs':
                $results = Rfq::search($query)->take(50)->get()->map(fn($item) => $this->formatRfq($item));
                break;

            case 'auctions':
                $results = Auction::search($query)->take(50)->get()->map(fn($item) => $this->formatAuction($item));
                break;

            default: // 'all'
                $results = collect()
                    ->merge(User::search($query)->take(10)->get()->map(fn($item) => $this->formatUser($item)))
                    ->merge(Company::search($query)->take(10)->get()->map(fn($item) => $this->formatCompany($item)))
                    ->merge(Project::search($query)->take(10)->get()->map(fn($item) => $this->formatProject($item)))
                    ->merge(Rfq::search($query)->take(10)->get()->map(fn($item) => $this->formatRfq($item)))
                    ->merge(Auction::search($query)->take(10)->get()->map(fn($item) => $this->formatAuction($item)));
                break;
        }

        return $results;
    }

    /**
     * Получить количество результатов по каждому типу
     */
    protected function getSearchCounts(string $query): array
    {
        // Scout database driver doesn't support count() on builder,
        // so we need to get results and count them
        return [
            'all' => 0,
            'users' => User::search($query)->get()->count(),
            'companies' => Company::search($query)->get()->count(),
            'projects' => Project::search($query)->get()->count(),
            'rfqs' => Rfq::search($query)->get()->count(),
            'auctions' => Auction::search($query)->get()->count(),
        ];
    }

    /**
     * Пустые счётчики для пустого запроса
     */
    protected function getEmptyCounts(): array
    {
        return [
            'all' => 0,
            'users' => 0,
            'companies' => 0,
            'projects' => 0,
            'rfqs' => 0,
            'auctions' => 0,
        ];
    }

    /**
     * Форматирование пользователя
     */
    protected function formatUser(User $user): array
    {
        return [
            'type' => 'user',
            'type_label' => 'Пользователь',
            'icon' => 'user',
            'id' => $user->id,
            'title' => $user->name,
            'subtitle' => $user->position,
            'description' => $user->bio,
            'url' => route('profile.edit'), // TODO: Публичный профиль пользователя
            'avatar' => $user->avatar,
        ];
    }

    /**
     * Форматирование компании
     */
    protected function formatCompany(Company $company): array
    {
        return [
            'type' => 'company',
            'type_label' => 'Компания',
            'icon' => 'building',
            'id' => $company->id,
            'title' => $company->name,
            'subtitle' => $company->inn ? "ИНН: {$company->inn}" : null,
            'description' => $company->short_description,
            'url' => route('companies.show', $company),
            'avatar' => $company->logo_url,
            'is_verified' => $company->is_verified,
        ];
    }

    /**
     * Форматирование проекта
     */
    protected function formatProject(Project $project): array
    {
        return [
            'type' => 'project',
            'type_label' => 'Проект',
            'icon' => 'folder',
            'id' => $project->id,
            'title' => $project->name,
            'subtitle' => $project->company?->name,
            'description' => $project->description,
            'url' => route('projects.show', $project->slug ?? $project->id),
            'status' => $project->status,
        ];
    }

    /**
     * Форматирование RFQ
     */
    protected function formatRfq(Rfq $rfq): array
    {
        return [
            'type' => 'rfq',
            'type_label' => 'Запрос котировок',
            'icon' => 'document-text',
            'id' => $rfq->id,
            'title' => $rfq->title,
            'subtitle' => $rfq->number,
            'description' => $rfq->description,
            'url' => route('rfqs.show', $rfq),
            'status' => $rfq->status,
        ];
    }

    /**
     * Форматирование аукциона
     */
    protected function formatAuction(Auction $auction): array
    {
        return [
            'type' => 'auction',
            'type_label' => 'Аукцион',
            'icon' => 'currency-dollar',
            'id' => $auction->id,
            'title' => $auction->title,
            'subtitle' => $auction->number,
            'description' => $auction->description,
            'url' => route('auctions.show', $auction),
            'status' => $auction->status,
        ];
    }
}
