<?php

namespace App\Orchid\Screens;

use App\Models\Project;
use App\Models\Company;
use Orchid\Screen\Screen;
use Orchid\Screen\Actions\Link;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\TD;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\Input;
use Illuminate\Http\Request;

class ProjectListScreen extends Screen
{
    /**
     * Query data.
     */
    public function query(Request $request): iterable
    {
        $query = Project::with(['company', 'creator']);

        // Фильтр по поиску
        if ($request->filled('filter.search')) {
            $query->where('name', 'like', '%' . $request->input('filter.search') . '%');
        }

        // Фильтр по статусу
        if ($request->filled('filter.status')) {
            $query->where('status', $request->input('filter.status'));
        }

        // Фильтр по компании
        if ($request->filled('filter.company_id')) {
            $query->where('company_id', $request->input('filter.company_id'));
        }

        return [
            'projects' => $query->orderBy('created_at', 'desc')->paginate(20),
        ];
    }

    /**
     * Display header name.
     */
    public function name(): ?string
    {
        return 'Проекты';
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        return 'Список всех проектов на платформе';
    }

    /**
     * Button commands.
     */
    public function commandBar(): iterable
    {
        return [
            Link::make('Создать проект')
                ->icon('plus')
                ->route('platform.projects.create'),
        ];
    }

    /**
     * Views.
     */
    public function layout(): iterable
    {
        return [
            Layout::rows([
                Input::make('filter.search')
                    ->title('Поиск')
                    ->placeholder('Поиск по названию проекта')
                    ->value(request('filter.search')),

                Select::make('filter.status')
                    ->title('Статус')
                    ->options(Project::getStatuses())
                    ->empty('Все статусы')
                    ->value(request('filter.status')),

                Select::make('filter.company_id')
                    ->title('Компания-заказчик')
                    ->fromModel(Company::class, 'name', 'id')
                    ->empty('Все компании')
                    ->value(request('filter.company_id')),
            ]),

            Layout::table('projects', [
                TD::make('id', 'ID')
                    ->sort()
                    ->cantHide(),

                TD::make('name', 'Название')
                    ->sort()
                    ->render(fn (Project $project) => Link::make($project->name)
                        ->route('platform.projects.edit', $project)),

                TD::make('company', 'Компания-заказчик')
                    ->render(fn (Project $project) => $project->company->name ?? '—'),

                TD::make('status', 'Статус')
                    ->sort()
                    ->render(function (Project $project) {
                        $badges = [
                            'active' => 'success',
                            'completed' => 'info',
                            'cancelled' => 'danger',
                        ];
                        
                        $statusText = Project::getStatuses()[$project->status] ?? $project->status;
                        $badgeClass = $badges[$project->status] ?? 'secondary';
                        
                        return "<span class='badge bg-{$badgeClass}'>{$statusText}</span>";
                    }),

                TD::make('start_date', 'Дата начала')
                    ->sort()
                    ->render(fn (Project $project) => $project->start_date?->format('d.m.Y') ?? '—'),

                TD::make('end_date', 'Дата окончания')
                    ->render(fn (Project $project) => 
                        $project->is_ongoing 
                            ? '<span class="badge bg-primary">По настоящее время</span>' 
                            : ($project->end_date?->format('d.m.Y') ?? '—')
                    ),

                TD::make('creator', 'Создатель')
                    ->render(fn (Project $project) => $project->creator->name ?? '—'),

                TD::make('created_at', 'Создан')
                    ->sort()
                    ->render(fn (Project $project) => $project->created_at->format('d.m.Y H:i')),

                TD::make('actions', 'Действия')
                    ->align(TD::ALIGN_CENTER)
                    ->width('100px')
                    ->render(fn (Project $project) => 
                        Link::make('Редактировать')
                            ->icon('pencil')
                            ->route('platform.projects.edit', $project)
                    ),
            ]),
        ];
    }
}