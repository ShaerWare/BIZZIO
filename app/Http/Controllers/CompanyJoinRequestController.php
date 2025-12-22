<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyJoinRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CompanyJoinRequestController extends Controller
{
    use AuthorizesRequests;

    /**
     * Отправить запрос на присоединение
     */
    public function store(Request $request, Company $company)
    {
        // Проверка: пользователь уже модератор?
        if ($company->isModerator(auth()->user())) {
            return back()->with('error', 'Вы уже являетесь модератором этой компании');
        }

        // Проверка: есть ли активный запрос?
        if ($company->hasPendingRequestFrom(auth()->user())) {
            return back()->with('error', 'Вы уже отправили запрос на присоединение к этой компании');
        }

        $validated = $request->validate([
            'desired_role' => 'nullable|string|max:100',
            'message' => 'nullable|string|max:1000',
        ]);

        CompanyJoinRequest::create([
            'company_id' => $company->id,
            'user_id' => auth()->id(),
            'desired_role' => $validated['desired_role'] ?? null,
            'message' => $validated['message'] ?? null,
            'status' => 'pending',
        ]);

        // TODO: Отправить уведомление модераторам компании (Спринт 7)

        return back()->with('success', 'Запрос на присоединение отправлен! Ожидайте ответа от модераторов компании.');
    }

    /**
     * Отозвать запрос
     */
    public function destroy(CompanyJoinRequest $request)
    {
        if (!$request->canCancel(auth()->user())) {
            abort(403, 'Вы не можете отозвать этот запрос');
        }

        $request->delete();

        return back()->with('success', 'Запрос отозван');
    }

    /**
     * Одобрить запрос
     */
    public function approve(CompanyJoinRequest $joinRequest, Request $request)
    {
        if (!$joinRequest->canReview(auth()->user())) {
            abort(403, 'У вас нет прав для рассмотрения этого запроса');
        }

        $validated = $request->validate([
            'role' => 'nullable|string|max:100',
            'can_manage_moderators' => 'boolean',
        ]);

        DB::beginTransaction();

        try {
            // Добавляем пользователя как модератора
            $joinRequest->company->assignModerator(
                $joinRequest->user,
                $validated['role'] ?? $joinRequest->desired_role,
                auth()->user(),
                $validated['can_manage_moderators'] ?? false
            );

            // Обновляем запрос
            $joinRequest->update([
                'status' => 'approved',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);

            DB::commit();

            // TODO: Отправить уведомление пользователю (Спринт 7)

            return back()->with('success', "Запрос одобрен. Пользователь {$joinRequest->user->name} добавлен как модератор.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Ошибка при одобрении: ' . $e->getMessage());
        }
    }

    /**
     * Отклонить запрос
     */
    public function reject(CompanyJoinRequest $joinRequest, Request $request)
    {
        if (!$joinRequest->canReview(auth()->user())) {
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

        // TODO: Отправить уведомление пользователю (Спринт 7)

        return back()->with('success', 'Запрос отклонён');
    }
}