<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class CompanyModeratorController extends Controller
{
    use AuthorizesRequests;

    /**
     * Добавить модератора в компанию (прямое приглашение)
     */
    public function store(Request $request, Company $company)
    {
        $actor = auth()->user();

        if (! $company->canAddMember($actor)) {
            abort(403, 'У вас нет прав для добавления участников этой компании');
        }

        $assignableRoles = $company->getAssignableMemberRoles($actor);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => ['nullable', 'string', 'in:'.implode(',', array_keys($assignableRoles))],
            'can_manage_moderators' => 'boolean',
        ]);

        $user = User::findOrFail($validated['user_id']);

        // Проверка: уже модератор?
        if ($company->isModerator($user)) {
            return back()->with('error', 'Этот пользователь уже является модератором компании');
        }

        // Обычный модератор не может выдавать флаг can_manage_moderators
        $canManageFlag = $company->canManageModerators($actor)
            ? ($validated['can_manage_moderators'] ?? false)
            : false;

        $company->assignModerator(
            $user,
            $validated['role'] ?? 'member',
            $actor,
            $canManageFlag
        );

        // TODO: Отправить уведомление пользователю (Спринт 7)

        return back()->with('success', "Пользователь {$user->name} добавлен как модератор компании");
    }

    /**
     * Обновить роль модератора
     */
    public function update(Request $request, Company $company, User $user)
    {
        if (! $company->canManageModerators(auth()->user())) {
            abort(403, 'У вас нет прав для управления модераторами этой компании');
        }

        $validated = $request->validate([
            'role' => 'nullable|string|max:100',
            'can_manage_moderators' => 'boolean',
        ]);

        $company->moderators()->updateExistingPivot($user->id, [
            'role' => $validated['role'] ?? null,
            'can_manage_moderators' => $validated['can_manage_moderators'] ?? false,
        ]);

        return back()->with('success', "Роль пользователя {$user->name} обновлена");
    }

    /**
     * Удалить модератора из компании
     */
    public function destroy(Company $company, User $user)
    {
        if (! $company->canManageModerators(auth()->user())) {
            abort(403, 'У вас нет прав для управления модераторами этой компании');
        }

        // Нельзя удалить создателя
        if ($company->created_by === $user->id) {
            return back()->with('error', 'Нельзя удалить создателя компании из модераторов');
        }

        $company->removeModerator($user);

        return back()->with('success', "Пользователь {$user->name} удалён из модераторов компании");
    }
}
