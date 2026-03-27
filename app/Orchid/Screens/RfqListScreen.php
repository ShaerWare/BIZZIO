<?php

declare(strict_types=1);

namespace App\Orchid\Screens;

use App\Models\Rfq;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

class RfqListScreen extends Screen
{
    /**
     * Query data.
     */
    public function query(): iterable
    {
        return [
            'rfqs' => Rfq::with(['company', 'creator', 'bids'])
                ->filters()
                ->defaultSort('created_at', 'desc')
                ->paginate(),
        ];
    }

    /**
     * Display header name.
     */
    public function name(): ?string
    {
        return 'Запросы цен (RFQ)';
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        return 'Управление запросами цен';
    }

    /**
     * Button commands.
     */
    public function commandBar(): iterable
    {
        return [
            Link::make('Создать RFQ')
                ->icon('plus')
                ->route('platform.rfqs.create'),
        ];
    }

    /**
     * Views.
     */
    public function layout(): iterable
    {
        return [
            Layout::table('rfqs', [
                TD::make('number', 'Номер')
                    ->sort()
                    ->filter(TD::FILTER_TEXT)
                    ->render(function (Rfq $rfq) {
                        return Link::make($rfq->number)
                            ->route('platform.rfqs.edit', $rfq);
                    }),

                TD::make('title', 'Название')
                    ->sort()
                    ->filter(TD::FILTER_TEXT)
                    ->render(function (Rfq $rfq) {
                        return e($rfq->title);
                    }),

                TD::make('company.name', 'Организатор')
                    ->sort()
                    ->render(function (Rfq $rfq) {
                        return e($rfq->company->name);
                    }),

                TD::make('type', 'Тип')
                    ->sort()
                    ->render(function (Rfq $rfq) {
                        $badges = [
                            'open' => '<span class="badge bg-info">Открытая</span>',
                            'closed' => '<span class="badge bg-warning">Закрытая</span>',
                        ];

                        return $badges[$rfq->type] ?? '';
                    }),

                TD::make('status', 'Статус')
                    ->sort()
                    ->render(function (Rfq $rfq) {
                        $badges = [
                            'draft' => '<span class="badge bg-secondary">Черновик</span>',
                            'active' => '<span class="badge bg-success">Активный</span>',
                            'closed' => '<span class="badge bg-dark">Завершён</span>',
                            'cancelled' => '<span class="badge bg-danger">Отменён</span>',
                        ];

                        return $badges[$rfq->status] ?? '';
                    }),

                TD::make('bids_count', 'Заявок')
                    ->render(function (Rfq $rfq) {
                        return $rfq->bids->count();
                    }),

                TD::make('end_date', 'Окончание')
                    ->sort()
                    ->render(function (Rfq $rfq) {
                        return $rfq->end_date->format('d.m.Y H:i');
                    }),

                TD::make('created_at', 'Создан')
                    ->sort()
                    ->render(function (Rfq $rfq) {
                        return $rfq->created_at->format('d.m.Y H:i');
                    }),

                TD::make('actions', 'Действия')
                    ->align(TD::ALIGN_CENTER)
                    ->width('150px')
                    ->render(function (Rfq $rfq) {
                        return Link::make('Ред.')
                            ->icon('pencil')
                            ->route('platform.rfqs.edit', $rfq)
                            ->class('btn btn-sm btn-link') .
                        Button::make('Удалить')
                            ->icon('trash')
                            ->confirm('Вы уверены, что хотите удалить RFQ «' . $rfq->number . '»?')
                            ->method('remove', ['rfq' => $rfq->id])
                            ->class('btn btn-sm btn-link text-danger');
                    }),
            ]),
        ];
    }

    /**
     * Удаление RFQ (soft delete).
     */
    public function remove(Request $request): void
    {
        $rfq = Rfq::findOrFail($request->get('rfq'));
        $rfq->delete();

        \Orchid\Support\Facades\Toast::info('Запрос цен «' . $rfq->number . '» удалён.');
    }
}
