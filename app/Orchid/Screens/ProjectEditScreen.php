<?php

namespace App\Orchid\Screens;

use App\Models\Project;
use App\Models\Company;
use Illuminate\Http\Request;
use Orchid\Screen\Screen;
use Orchid\Screen\Actions\Button;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Quill;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\Picture;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\Relation;
use Orchid\Support\Facades\Alert;
use Orchid\Support\Facades\Toast;
use Illuminate\Support\Str;

class ProjectEditScreen extends Screen
{
    /**
     * @var Project
     */
    public $project;

    /**
     * Query data.
     */
    public function query(Project $project): iterable
    {
        $project->load(['company', 'participants']);

        return [
            'project' => $project,
        ];
    }

    /**
     * Display header name.
     */
    public function name(): ?string
    {
        return $this->project->exists ? 'Редактирование проекта' : 'Создание проекта';
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        return $this->project->exists 
            ? 'Редактирование проекта: ' . $this->project->name 
            : 'Создание нового проекта';
    }

    /**
     * Button commands.
     */
    public function commandBar(): iterable
    {
        return [
            Button::make('Сохранить')
                ->icon('check')
                ->method('save'),

            Button::make('Удалить')
                ->icon('trash')
                ->method('remove')
                ->confirm('Вы уверены, что хотите удалить этот проект?')
                ->canSee($this->project->exists),
        ];
    }

    /**
     * Views.
     */
    public function layout(): iterable
    {
        return [
            Layout::rows([
                Input::make('project.name')
                    ->title('Название проекта')
                    ->placeholder('Введите название проекта')
                    ->required()
                    ->help('Название будет автоматически преобразовано в URL-адрес'),

                Quill::make('project.description')
                    ->title('Краткое описание')
                    ->placeholder('Введите краткое описание проекта (до 500 символов)')
                    ->help('Это описание будет отображаться в каталоге проектов'),

                Quill::make('project.full_description')
                    ->title('Полное описание')
                    ->placeholder('Введите подробное описание проекта')
                    ->help('Подробное описание проекта с целями, задачами и результатами'),

                Picture::make('project.avatar')
                    ->title('Аватар проекта')
                    ->targetRelativeUrl()
                    ->storage('public')
                    ->maxFileSize(2) // 2MB
                    ->acceptedFiles('image/*')
                    ->help('Рекомендуемый размер: 800x600px. Максимум 2MB'),

                Relation::make('project.company_id')
                    ->title('Компания-заказчик')
                    ->fromModel(Company::class, 'name')
                    ->applyScope('verified')
                    ->required()
                    ->help('Выберите компанию, от имени которой создаётся проект'),

                DateTimer::make('project.start_date')
                    ->title('Дата начала проекта')
                    ->format('Y-m-d')
                    ->required()
                    ->help('Дата начала выполнения проекта'),

                CheckBox::make('project.is_ongoing')
                    ->title('Проект продолжается')
                    ->placeholder('Проект выполняется по настоящее время')
                    ->sendTrueOrFalse()
                    ->help('Если проект ещё не завершён, отметьте этот чекбокс'),

                DateTimer::make('project.end_date')
                    ->title('Дата окончания проекта')
                    ->format('Y-m-d')
                    ->help('Дата завершения проекта (если проект завершён)'),

                Select::make('project.status')
                    ->title('Статус проекта')
                    ->options(Project::getStatuses())
                    ->required()
                    ->help('Текущий статус выполнения проекта'),
            ]),

            Layout::rows([
                Relation::make('project.participants.')
                    ->title('Компании-участники')
                    ->fromModel(Company::class, 'name')
                    ->applyScope('verified')
                    ->multiple()
                    ->help('Выберите компании, участвующие в проекте. Роли можно будет назначить после сохранения через веб-интерфейс'),
            ])->title('Участники проекта'),
        ];
    }

    /**
     * Save project.
     */
    public function save(Request $request, Project $project)
    {
        $data = $request->get('project');

        // Валидация
        $request->validate([
            'project.name' => 'required|string|max:255',
            'project.company_id' => 'required|exists:companies,id',
            'project.start_date' => 'required|date',
            'project.end_date' => 'nullable|date|after_or_equal:project.start_date',
            'project.status' => 'required|in:active,completed,cancelled',
        ]);

        // Если проект продолжается, убираем дату окончания
        if (!empty($data['is_ongoing'])) {
            $data['end_date'] = null;
        }

        // Генерация slug
        if (empty($project->id) || $data['name'] !== $project->name) {
            $data['slug'] = Str::slug($data['name']);
        }

        // Установка created_by при создании
        if (empty($project->id)) {
            $data['created_by'] = auth()->id();
        }

        // Сохранение проекта
        $project->fill($data)->save();

        // Синхронизация участников (если указаны)
        if (!empty($data['participants'])) {
            $project->participants()->sync($data['participants']);
        }

        Toast::info('Проект успешно сохранён!');

        return redirect()->route('platform.projects');
    }

    /**
     * Remove project.
     */
    public function remove(Project $project)
    {
        $project->delete();

        Toast::info('Проект успешно удалён!');

        return redirect()->route('platform.projects');
    }
}