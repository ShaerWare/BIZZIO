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
            'retention_days' => Setting::documentsRetentionDays(),
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
                Input::make('retention_days')
                    ->type('number')
                    ->min(1)
                    ->max(3650)
                    ->required()
                    ->title('Срок хранения конкурсной документации (дни)')
                    ->help('#296: сколько дней после завершения процедуры участники и организатор могут скачивать документацию. По истечении срока файлы автоматически удаляются с сервера. Протоколы не удаляются.'),
            ]),
        ];
    }

    public function save(Request $request): void
    {
        $data = $request->validate([
            'retention_days' => 'required|integer|min:1|max:3650',
        ]);

        Setting::put(Setting::DOCUMENTS_RETENTION_DAYS, (int) $data['retention_days']);

        Toast::info('Настройки документации сохранены.');
    }
}
