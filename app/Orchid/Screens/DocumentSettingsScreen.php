<?php

declare(strict_types=1);

namespace App\Orchid\Screens;

use App\Models\Setting;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

/**
 * #185 Настройки конкурсной документации: срок хранения (автоудаление).
 */
class DocumentSettingsScreen extends Screen
{
    /**
     * @return array<string, mixed>
     */
    public function query(): iterable
    {
        return [
            'retention_months' => Setting::documentsRetentionMonths(),
        ];
    }

    public function name(): ?string
    {
        return 'Настройки документации';
    }

    public function description(): ?string
    {
        return 'Конкурсная документация процедур (Извещение / ТЗ / Проект договора / Прочие файлы).';
    }

    /**
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            Button::make('Сохранить')
                ->icon('bs.save')
                ->method('save'),
        ];
    }

    /**
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        return [
            Layout::rows([
                Input::make('retention_months')
                    ->type('number')
                    ->min(1)
                    ->max(120)
                    ->required()
                    ->title('Срок хранения конкурсной документации (месяцы)')
                    ->help('По истечении срока после завершения процедуры файлы конкурсной документации автоматически удаляются с сервера. Протоколы не удаляются.'),
            ]),
        ];
    }

    public function save(Request $request): void
    {
        $data = $request->validate([
            'retention_months' => 'required|integer|min:1|max:120',
        ]);

        Setting::put(Setting::DOCUMENTS_RETENTION_MONTHS, (int) $data['retention_months']);

        Toast::info('Настройки документации сохранены.');
    }
}
