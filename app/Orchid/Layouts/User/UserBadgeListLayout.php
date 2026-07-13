<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\User;

use App\Models\UserBadge;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;
use Orchid\Support\Color;

/**
 * Таблица текущих бейджей пользователя с предпросмотром и кнопкой удаления.
 */
class UserBadgeListLayout extends Table
{
    /**
     * @var string
     */
    public $target = 'badges';

    /**
     * @return TD[]
     */
    public function columns(): array
    {
        return [
            TD::make('preview', __('Бейдж'))
                ->render(fn (UserBadge $badge) => sprintf(
                    '<span style="display:inline-block;border:2px solid %s;color:%s;border-radius:9999px;padding:2px 10px;font-size:12px;font-weight:600;">%s</span>',
                    e($badge->color),
                    e($badge->color),
                    $badge->label !== null && $badge->label !== '' ? e($badge->label) : '—'
                )),

            TD::make('color', __('Цвет'))
                ->render(fn (UserBadge $badge) => e($badge->color)),

            TD::make('created_at', __('Выдан'))
                ->render(fn (UserBadge $badge) => optional($badge->created_at)->format('d.m.Y H:i')),

            TD::make(__('Действия'))
                ->align(TD::ALIGN_CENTER)
                ->width('120px')
                ->render(fn (UserBadge $badge) => Button::make(__('Удалить'))
                    ->icon('bs.trash3')
                    ->type(Color::DANGER)
                    ->confirm(__('Удалить этот бейдж?'))
                    ->method('removeBadge', ['badge' => $badge->id])),
        ];
    }
}
