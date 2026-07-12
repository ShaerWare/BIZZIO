<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\User;

use App\Models\User;
use Orchid\Screen\Field;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Layouts\Rows;

/**
 * Модал массовой выдачи бейджа: выбор нескольких пользователей + параметры бейджа.
 */
class BadgeAssignLayout extends Rows
{
    /**
     * @return Field[]
     */
    public function fields(): array
    {
        return array_merge([
            Relation::make('assign_users.')
                ->fromModel(User::class, 'name')
                ->multiple()
                ->required()
                ->title(__('Пользователи'))
                ->help(__('Кому выдать бейдж')),
        ], BadgeFormLayout::badgeFields());
    }
}
