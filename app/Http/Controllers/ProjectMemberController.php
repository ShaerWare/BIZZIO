<?php

namespace App\Http\Controllers;

use App\Events\ProjectJoinRequestCreated;
use App\Events\ProjectJoinRequestReviewed;
use App\Events\ProjectUserInvited;
use App\Models\Project;
use App\Models\ProjectJoinRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectMemberController extends Controller
{
    /**
     * Пригласить пользователя в проект (для admin/moderator проекта)
     */
    public function store(Request $request, Project $project)
    {
        if (! $project->canManage(auth()->user())) {
            abort(403, 'У вас нет прав для управления участниками этого проекта');
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:admin,moderator,member',
        ]);

        $user = User::findOrFail($validated['user_id']);

        // Проверка: уже участник?
        if ($project->isMember($user)) {
            return back()->with('error', 'Этот пользователь уже является участником проекта');
        }

        // Проверка: пользователь принадлежит к компании-участнику или компании-владельцу
        $participantCompanyIds = $project->participants()->pluck('companies.id')->toArray();
        $participantCompanyIds[] = $project->company_id;

        $userCompanyIds = $user->moderatedCompanies()->pluck('companies.id')->toArray();
        $userParticipatingCompany = collect($userCompanyIds)->intersect($participantCompanyIds)->first();

        if (! $userParticipatingCompany) {
            return back()->with('error', 'Пользователь не принадлежит к компании-участнику проекта');
        }

        $project->addMember($user, \App\Models\Company::find($userParticipatingCompany), $validated['role'], auth()->user());

        event(new ProjectUserInvited($project, $user, auth()->user()));

        return back()->with('success', "Пользователь {$user->name} добавлен в проект");
    }

    /**
     * Обновить роль участника
     */
    public function update(Request $request, Project $project, User $user)
    {
        if (! $project->canManage(auth()->user())) {
            abort(403, 'У вас нет прав для управления участниками этого проекта');
        }

        $validated = $request->validate([
            'role' => 'required|in:admin,moderator,member',
        ]);

        $project->members()->updateExistingPivot($user->id, [
            'role' => $validated['role'],
        ]);

        return back()->with('success', "Роль пользователя {$user->name} обновлена");
    }

    /**
     * Удалить участника из проекта
     */
    public function destroy(Project $project, User $user)
    {
        if (! $project->canManage(auth()->user())) {
            abort(403, 'У вас нет прав для управления участниками этого проекта');
        }

        // Нельзя удалить создателя проекта
        if ($project->created_by === $user->id) {
            return back()->with('error', 'Нельзя удалить создателя проекта из участников');
        }

        $project->removeMember($user);

        return back()->with('success', "Пользователь {$user->name} удалён из проекта");
    }

    /**
     * Подать запрос на присоединение к проекту
     */
    public function storeJoinRequest(Request $request, Project $project)
    {
        $user = auth()->user();

        // Проверка: уже участник?
        if ($project->isMember($user)) {
            return back()->with('error', 'Вы уже являетесь участником этого проекта');
        }

        // Проверка: есть ли активный запрос?
        if ($project->hasPendingRequestFrom($user)) {
            return back()->with('error', 'Вы уже отправили запрос на присоединение к этому проекту');
        }

        // Проверка: компания пользователя участвует в проекте
        $participantCompanyIds = $project->participants()->pluck('companies.id')->toArray();
        $participantCompanyIds[] = $project->company_id;

        $userCompanyIds = $user->moderatedCompanies()->pluck('companies.id')->toArray();
        $userParticipatingCompany = collect($userCompanyIds)->intersect($participantCompanyIds)->first();

        if (! $userParticipatingCompany) {
            return back()->with('error', 'Ваша компания не является участником этого проекта');
        }

        $validated = $request->validate([
            'desired_role' => 'nullable|string|max:100',
            'message' => 'nullable|string|max:1000',
        ]);

        $joinRequest = ProjectJoinRequest::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'company_id' => $userParticipatingCompany,
            'desired_role' => $validated['desired_role'] ?? null,
            'message' => $validated['message'] ?? null,
            'status' => 'pending',
        ]);

        event(new ProjectJoinRequestCreated($joinRequest));

        return back()->with('success', 'Запрос на присоединение отправлен! Ожидайте ответа.');
    }

    /**
     * Одобрить запрос на присоединение
     */
    public function approveJoinRequest(ProjectJoinRequest $joinRequest)
    {
        if (! $joinRequest->canReview(auth()->user())) {
            abort(403, 'У вас нет прав для рассмотрения этого запроса');
        }

        DB::beginTransaction();

        try {
            $role = $joinRequest->desired_role && in_array($joinRequest->desired_role, ['admin', 'moderator', 'member'])
                ? $joinRequest->desired_role
                : 'member';

            $joinRequest->project->addMember(
                $joinRequest->user,
                $joinRequest->company,
                $role,
                auth()->user()
            );

            $joinRequest->update([
                'status' => 'approved',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);

            DB::commit();

            event(new ProjectJoinRequestReviewed($joinRequest, 'approved'));

            return back()->with('success', "Запрос одобрен. Пользователь {$joinRequest->user->name} добавлен в проект.");
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Ошибка при одобрении: '.$e->getMessage());
        }
    }

    /**
     * Отклонить запрос на присоединение
     */
    public function rejectJoinRequest(Request $request, ProjectJoinRequest $joinRequest)
    {
        if (! $joinRequest->canReview(auth()->user())) {
            abort(403, 'У вас нет прав для рассмотрения этого запроса');
        }

        $validated = $request->validate([
            'review_comment' => 'nullable|string|max:500',
        ]);

        $joinRequest->update([
            'status' => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_comment' => $validated['review_comment'] ?? null,
        ]);

        event(new ProjectJoinRequestReviewed($joinRequest, 'rejected'));

        return back()->with('success', 'Запрос отклонён');
    }

    /**
     * Отозвать свой запрос на присоединение
     */
    public function destroyJoinRequest(ProjectJoinRequest $joinRequest)
    {
        if (! $joinRequest->canCancel(auth()->user())) {
            abort(403, 'Вы не можете отозвать этот запрос');
        }

        $joinRequest->delete();

        return back()->with('success', 'Запрос отозван');
    }
}
