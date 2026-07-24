<?php

declare(strict_types=1);

namespace App\Orchid\Screens\User;

use App\Models\User;
use App\Models\UserBadge;
use App\Orchid\Layouts\User\BadgeAssignLayout;
use App\Orchid\Layouts\User\UserEditLayout;
use App\Orchid\Layouts\User\UserFiltersLayout;
use App\Orchid\Layouts\User\UserListLayout;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class UserListScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            'users' => User::with('roles')
                ->filters(UserFiltersLayout::class)
                ->defaultSort('id', 'desc')
                ->paginate(),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     */
    public function name(): ?string
    {
        return 'User Management';
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        return 'A comprehensive list of all registered users, including their profiles and privileges.';
    }

    public function permission(): ?iterable
    {
        return [
            'platform.systems.users',
        ];
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            ModalToggle::make(__('Выдать бейдж'))
                ->icon('bs.award')
                ->modal('assignBadgeModal')
                ->method('assignBadge'),

            Link::make(__('Add'))
                ->icon('bs.plus-circle')
                ->route('platform.systems.users.create'),
        ];
    }

    /**
     * The screen's layout elements.
     *
     * @return string[]|\Orchid\Screen\Layout[]
     */
    public function layout(): iterable
    {
        return [
            UserFiltersLayout::class,
            UserListLayout::class,

            Layout::modal('editUserModal', UserEditLayout::class)
                ->deferred('loadUserOnOpenModal'),

            Layout::modal('assignBadgeModal', BadgeAssignLayout::class)
                ->title(__('Выдать бейдж'))
                ->applyButton(__('Выдать')),
        ];
    }

    /**
     * Loads user data when opening the modal window.
     *
     * @return array
     */
    public function loadUserOnOpenModal(User $user): iterable
    {
        return [
            'user' => $user,
        ];
    }

    public function saveUser(Request $request, User $user): void
    {
        $request->validate([
            'user.email' => [
                'required',
                Rule::unique(User::class, 'email')->ignore($user),
            ],
        ]);

        $user->fill($request->input('user'))->save();

        Toast::info(__('User was saved.'));
    }

    public function remove(Request $request): void
    {
        User::findOrFail($request->get('id'))->delete();

        Toast::info(__('User was removed'));
    }

    /**
     * Массовая выдача одного бейджа выбранным пользователям.
     */
    public function assignBadge(Request $request): void
    {
        $data = $request->validate([
            'assign_users' => ['required', 'array'],
            'assign_users.*' => ['integer', 'exists:users,id'],
            'badge.color_preset' => ['required', 'string'],
            'badge.color_custom' => ['nullable', 'string', 'max:20'],
            'badge.label_preset' => ['required', 'string'],
            'badge.label_custom' => ['nullable', 'string', 'max:255'],
        ]);

        $color = UserBadge::resolveColor($data['badge']['color_preset'], $data['badge']['color_custom'] ?? null);
        $label = UserBadge::resolveLabel($data['badge']['label_preset'], $data['badge']['label_custom'] ?? null);
        $adminId = $request->user()->id;

        foreach ($data['assign_users'] as $userId) {
            UserBadge::create([
                'user_id' => $userId,
                'color' => $color,
                'label' => $label,
                'created_by' => $adminId,
            ]);
        }

        Toast::info(__('Бейдж выдан :count пользователям.', ['count' => count($data['assign_users'])]));
    }
}
