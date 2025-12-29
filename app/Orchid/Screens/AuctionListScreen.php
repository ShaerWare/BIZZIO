<?php

namespace App\Orchid\Screens;

use App\Models\Auction;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Screen\Actions\Link;
use Orchid\Support\Facades\Layout;

class AuctionListScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     */
    public function query(): iterable
    {
        return [
            'auctions' => Auction::with(['company', 'creator', 'bids'])
                ->filters()
                ->defaultSort('created_at', 'desc')
                ->paginate(20),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     */
    public function name(): ?string
    {
        return 'Аукционы';
    }

    /**
     * The description of the screen displayed in the header.
     */
    public function description(): ?string
    {
        return 'Управление аукционами';
    }

    /**
     * The screen's action buttons.
     */
    public function commandBar(): iterable
    {
        return [
            Link::make('Создать аукцион')
                ->icon('plus')
                ->route('platform.auctions.create'),
        ];
    }

    /**
     * The screen's layout elements.
     */
    public function layout(): iterable
    {
        return [
            Layout::table('auctions', [
                TD::make('number', 'Номер')
                    ->sort()
                    ->filter(TD::FILTER_TEXT)
                    ->render(fn (Auction $auction) => Link::make($auction->number)
                        ->route('platform.auctions.edit', $auction)),

                TD::make('title', 'Название')
                    ->sort()
                    ->filter(TD::FILTER_TEXT)
                    ->width('250px'),

                TD::make('company', 'Организатор')
                    ->render(fn (Auction $auction) => $auction->company->name),

                TD::make('type', 'Тип')
                    ->sort()
                    ->render(fn (Auction $auction) => $auction->type === 'open' ? 'Открытая' : 'Закрытая'),

                TD::make('status', 'Статус')
                    ->sort()
                    ->render(function (Auction $auction) {
                        $badges = [
                            'draft' => '<span class="badge bg-warning">Черновик</span>',
                            'active' => '<span class="badge bg-success">Приём заявок</span>',
                            'trading' => '<span class="badge bg-primary">Торги</span>',
                            'closed' => '<span class="badge bg-secondary">Завершён</span>',
                            'cancelled' => '<span class="badge bg-danger">Отменён</span>',
                        ];
                        
                        return $badges[$auction->status] ?? $auction->status;
                    }),

                TD::make('bids_count', 'Заявок/Ставок')
                    ->render(fn (Auction $auction) => $auction->bids->count()),

                TD::make('end_date', 'Дата окончания')
                    ->sort()
                    ->render(fn (Auction $auction) => $auction->end_date->format('d.m.Y H:i')),

                TD::make('created_at', 'Создан')
                    ->sort()
                    ->render(fn (Auction $auction) => $auction->created_at->format('d.m.Y')),
            ]),
        ];
    }
}