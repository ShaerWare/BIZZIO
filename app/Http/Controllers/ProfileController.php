<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Mail\FeedbackMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Update the user's avatar.
     */
    public function updateAvatar(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
        ]);

        $user = $request->user();

        // Удалить старый аватар, если это локальный файл
        if ($user->avatar && ! str_starts_with($user->avatar, 'http')) {
            Storage::disk('public')->delete($user->avatar);
        }

        // Сохранить новый аватар
        $path = $request->file('avatar')->store('avatars', 'public');
        $user->avatar = $path;
        $user->save();

        return Redirect::route('profile.edit')->with('status', 'avatar-updated');
    }

    /**
     * Remove the user's avatar.
     */
    public function destroyAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Удалить файл аватара, если это локальный файл
        if ($user->avatar && ! str_starts_with($user->avatar, 'http')) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->avatar = null;
        $user->save();

        return Redirect::route('profile.edit')->with('status', 'avatar-removed');
    }

    /**
     * G14: Send feedback to admin.
     */
    public function feedback(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $user = $request->user();

        // ADMIN_NOTIFICATION_EMAIL может содержать несколько адресов через запятую.
        $recipients = collect(explode(',', (string) config('app.admin_email')))
            ->map(fn (string $email): string => trim($email))
            ->filter()
            ->values()
            ->all();

        if ($recipients === []) {
            Log::error('Обратная связь: не задан ADMIN_NOTIFICATION_EMAIL, сообщение не отправлено', [
                'user_id' => $user->id,
            ]);

            return Redirect::route('profile.edit')->with('status', 'feedback-failed');
        }

        try {
            Mail::to($recipients)->send(new FeedbackMail(
                sender: $user,
                senderName: $validated['name'],
                senderCompany: $validated['company'] ?? null,
                body: $validated['message'],
            ));
        } catch (\Throwable $e) {
            Log::error('Обратная связь: ошибка отправки письма', [
                'user_id' => $user->id,
                'recipients' => $recipients,
                'error' => $e->getMessage(),
            ]);

            return Redirect::route('profile.edit')->with('status', 'feedback-failed');
        }

        Log::info('Обратная связь отправлена', [
            'user_id' => $user->id,
            'recipients' => $recipients,
        ]);

        return Redirect::route('profile.edit')->with('status', 'feedback-sent');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
