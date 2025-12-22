<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CompanyModeratorController extends Controller
{
    use AuthorizesRequests;

    /**
     * Добавить модератора в компанию (прямое приглашение)
     */
    public function store(Request $request, Company $company)
    {
        if (!$company->canManageModerators(auth()->user())) {
            abort(403, 'У вас нет прав для управления модераторами этой компании');
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'nullable|string|max:100',
            'can_manage_moderators' => 'boolean',
        ]);

        $user = User::findOrFail($validated['user_id']);

        // Проверка: уже модератор?
        if ($company->isModerator($user)) {
            return back()->with('error', 'Этот пользователь уже является модератором компании');
        }

        $company->assignModerator(
            $user,
            $validated['role'] ?? 'moderator',
            auth()->user(),
            $validated['can_manage_moderators'] ?? false
        );

        // TODO: Отправить уведомление пользователю (Спринт 7)

        return back()->with('success', "Пользователь {$user->name} добавлен как модератор компании");
    }

    /**
     * Обновить роль модератора
     */
    public function update(Request $request, Company $company, User $user)
    {
        if (!$company->canManageModerators(auth()->user())) {
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
        if (!$company->canManageModerators(auth()->user())) {
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