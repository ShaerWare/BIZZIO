<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Company;
use App\Models\Comment;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
    /**
     * Middleware для авторизации
     */
    public function __construct()
    {
        //$this->middleware('auth')->except(['index', 'show']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Project::with(['company', 'creator', 'participants']);

        // Поиск по названию
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Фильтр по статусу
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Фильтр по компании-заказчику
        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        $projects = $query->latest()->paginate(20);
        $companies = Company::verified()->get();

        return view('projects.index', compact('projects', 'companies'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Получаем компании, где пользователь является модератором
        $user = auth()->user();
        
        // ИСПРАВЛЕНО: hasRole() → inRole()
        if ($user->inRole('admin')) {
            $companies = Company::verified()->get();
        } else {
            $companies = $user->moderatedCompanies()->verified()->get();
        }

        // Если нет доступных компаний, редирект с ошибкой
        if ($companies->isEmpty()) {
            return redirect()->route('projects.index')
                ->with('error', 'Вы должны быть модератором хотя бы одной компании для создания проекта.');
        }

        // Список всех верифицированных компаний для приглашения участников
        $allCompanies = Company::verified()->get();

        return view('projects.create', compact('companies', 'allCompanies'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProjectRequest $request)
    {
        $validated = $request->validated();
        
        // Обработка загрузки аватара
        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('projects/avatars', 'public');
            $validated['avatar'] = $avatarPath;
        }

        // Установка created_by
        $validated['created_by'] = auth()->id();

        // Генерация slug
        $validated['slug'] = Str::slug($validated['name']);

        // Создание проекта
        $project = Project::create($validated);

        // Добавление участников проекта
        if ($request->has('participants')) {
            foreach ($request->participants as $participant) {
                $project->addParticipant(
                    Company::find($participant['company_id']),
                    $participant['role'],
                    $participant['participation_description'] ?? null
                );
            }
        }

        return redirect()->route('projects.show', $project->slug)
            ->with('success', 'Проект успешно создан!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project)
    {
        $project->load(['company', 'creator', 'participants', 'comments.user']);

        return view('projects.show', compact('project'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Project $project)
    {
        // Проверка прав
        if (!$project->canManage(auth()->user())) {
            abort(403, 'У вас нет прав для редактирования этого проекта.');
        }

        // Получаем компании, где пользователь является модератором
        $user = auth()->user();
        
        // ИСПРАВЛЕНО: hasRole() → inRole()
        if ($user->inRole('admin')) {
            $companies = Company::verified()->get();
        } else {
            $companies = $user->moderatedCompanies()->verified()->get();
        }

        $allCompanies = Company::verified()->get();

        return view('projects.edit', compact('project', 'companies', 'allCompanies'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProjectRequest $request, Project $project)
    {
        $validated = $request->validated();

        // Обработка загрузки нового аватара
        if ($request->hasFile('avatar')) {
            // Удаление старого аватара
            if ($project->avatar) {
                Storage::disk('public')->delete($project->avatar);
            }

            $avatarPath = $request->file('avatar')->store('projects/avatars', 'public');
            $validated['avatar'] = $avatarPath;
        }

        // Обновление slug при изменении названия
        if ($request->name !== $project->name) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        // Обновление проекта
        $project->update($validated);

        // Синхронизация участников
        if ($request->has('participants')) {
            // Удаляем всех текущих участников
            $project->participants()->detach();

            // Добавляем новых
            foreach ($request->participants as $participant) {
                $project->addParticipant(
                    Company::find($participant['company_id']),
                    $participant['role'],
                    $participant['participation_description'] ?? null
                );
            }
        }

        return redirect()->route('projects.show', $project->slug)
            ->with('success', 'Проект успешно обновлён!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project)
    {
        // ИСПРАВЛЕНО: hasRole() → inRole()
        if ($project->created_by !== auth()->id() && !auth()->user()->inRole('admin')) {
            abort(403, 'У вас нет прав для удаления этого проекта.');
        }

        // Удаление аватара
        if ($project->avatar) {
            Storage::disk('public')->delete($project->avatar);
        }

        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', 'Проект успешно удалён!');
    }

    // ========================
    // МЕТОДЫ ДЛЯ КОММЕНТАРИЕВ
    // ========================

    /**
     * Добавление комментария к проекту
     * POST /projects/{project}/comments
     */
    public function storeComment(Request $request, Project $project)
    {
        $request->validate([
            'body' => 'required|string|max:5000',
            'parent_id' => 'nullable|exists:project_comments,id',
        ], [
            'body.required' => 'Текст комментария обязателен.',
            'body.max' => 'Комментарий не должен превышать 5000 символов.',
            'parent_id.exists' => 'Родительский комментарий не найден.',
        ]);

        $comment = $project->allComments()->create([
            'user_id' => auth()->id(),
            'body' => $request->body,
            'parent_id' => $request->parent_id,
        ]);

        // Если это AJAX-запрос, возвращаем JSON
        if ($request->ajax()) {
            $comment->load('user');
            
            return response()->json([
                'success' => true,
                'message' => 'Комментарий успешно добавлен.',
                'comment' => $comment,
                'html' => view('projects.partials.comment', compact('comment'))->render(),
            ]);
        }

        return redirect()->route('projects.show', $project->slug)
            ->with('success', 'Комментарий успешно добавлен!');
    }

    /**
     * Редактирование комментария
     * PUT /comments/{comment}
     */
    public function updateComment(Request $request, Comment $comment)
    {
        // Проверка прав
        if (!$comment->canManage(auth()->user())) {
            abort(403, 'У вас нет прав для редактирования этого комментария.');
        }

        $request->validate([
            'body' => 'required|string|max:5000',
        ], [
            'body.required' => 'Текст комментария обязателен.',
            'body.max' => 'Комментарий не должен превышать 5000 символов.',
        ]);

        $comment->update(['body' => $request->body]);

        // Если это AJAX-запрос, возвращаем JSON
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Комментарий успешно обновлён.',
                'comment' => $comment,
            ]);
        }

        return redirect()->route('projects.show', $comment->project->slug)
            ->with('success', 'Комментарий успешно обновлён!');
    }

    /**
     * Удаление комментария
     * DELETE /comments/{comment}
     */
    public function destroyComment(Comment $comment)
    {
        // Проверка прав
        if (!$comment->canManage(auth()->user())) {
            abort(403, 'У вас нет прав для удаления этого комментария.');
        }

        $projectSlug = $comment->project->slug;
        $comment->delete();

        // Если это AJAX-запрос, возвращаем JSON
        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Комментарий успешно удалён.',
            ]);
        }

        return redirect()->route('projects.show', $projectSlug)
            ->with('success', 'Комментарий успешно удалён!');
    }
}