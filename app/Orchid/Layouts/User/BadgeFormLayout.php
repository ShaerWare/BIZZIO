<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\User;

use Orchid\Screen\Field;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Layouts\Rows;

/**
 * Общая форма выдачи бейджа: выбор цвета рамки (пресет/палитра) и подписи (пресет/кастом).
 * Переиспользуется на экране редактирования пользователя и в модале массовой выдачи.
 */
class BadgeFormLayout extends Rows
{
    /**
     * @return Field[]
     */
    public function fields(): array
    {
        return self::badgeFields();
    }

    /**
     * Поля выбора цвета и подписи (без указания пользователя).
     *
     * @return Field[]
     */
    public static function badgeFields(): array
    {
        return [
            Select::make('badge.color_preset')
                ->options([
                    'red' => 'Красный',
                    'bordeaux' => 'Бордовый',
                    'green' => 'Зелёный',
                    'custom' => 'Свой цвет (палитра)',
                ])
                ->value('red')
                ->required()
                ->title(__('Цвет рамки')),

            Input::make('badge.color_custom')
                ->type('color')
                ->value('#28a745')
                ->title(__('Свой цвет'))
                ->help(__('Используется, если выбран пункт «Свой цвет (палитра)»')),

            Select::make('badge.label_preset')
                ->options([
                    'suspicious' => 'Подозрительная личность',
                    'none' => 'Без подписи',
                    'confirmed' => 'Подтверждён',
                    'custom' => 'Своя надпись',
                ])
                ->value('confirmed')
                ->required()
                ->title(__('Подпись')),

            Input::make('badge.label_custom')
                ->type('text')
                ->max(255)
                ->title(__('Своя надпись'))
                ->placeholder(__('Текст подписи'))
                ->help(__('Используется, если выбран пункт «Своя надпись»')),
        ];
    }
}
