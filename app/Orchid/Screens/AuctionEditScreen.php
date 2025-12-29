<?php

namespace App\Orchid\Screens;

use App\Models\Auction;
use App\Models\Company;
use Orchid\Screen\Screen;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Upload;
use Orchid\Screen\Actions\Button;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;
use Illuminate\Http\Request;

class AuctionEditScreen extends Screen
{
    public $auction;

    /**
     * Fetch data to be displayed on the screen.
     */
    public function query(Auction $auction): iterable
    {
        return [
            'auction' => $auction,
        ];
    }

    /**
     * The name of the screen displayed in the header.
     */
    public function name(): ?string
    {
        return $this->auction->exists ? 'Редактировать аукцион' : 'Создать аукцион';
    }

    /**
     * The screen's action buttons.
     */
    public function commandBar(): iterable
    {
        return [
            Button::make('Сохранить')
                ->icon('check')
                ->method('save')
                ->canSee(!$this->auction->exists),

            Button::make('Обновить')
                ->icon('note')
                ->method('save')
                ->canSee($this->auction->exists && $this->auction->status === 'draft'),

            Button::make('Удалить')
                ->icon('trash')
                ->method('remove')
                ->canSee($this->auction->exists && $this->auction->status === 'draft')
                ->confirm('Вы уверены, что хотите удалить этот аукцион?'),
        ];
    }

    /**
     * The screen's layout elements.
     */
    public function layout(): iterable
    {
        return [
            Layout::rows([
                Input::make('auction.number')
                    ->title('Номер аукциона')
                    ->placeholder('Генерируется автоматически')
                    ->disabled()
                    ->canSee($this->auction->exists),

                Select::make('auction.company_id')
                    ->title('Компания-организатор')
                    ->fromModel(Company::class, 'name')
                    ->required()
                    ->disabled($this->auction->exists),

                Input::make('auction.title')
                    ->title('Название аукциона')
                    ->placeholder('Введите название')
                    ->required()
                    ->max(255),

                TextArea::make('auction.description')
                    ->title('Описание')
                    ->rows(5)
                    ->placeholder('Подробное описание предмета аукциона'),

                Select::make('auction.type')
                    ->title('Тип процедуры')
                    ->options([
                        'open' => 'Открытая',
                        'closed' => 'Закрытая',
                    ])
                    ->required()
                    ->disabled($this->auction->exists),

                DateTimer::make('auction.start_date')
                    ->title('Дата начала приёма заявок')
                    ->format('Y-m-d H:i')
                    ->required(),

                DateTimer::make('auction.end_date')
                    ->title('Дата окончания приёма заявок')
                    ->format('Y-m-d H:i')
                    ->required(),

                DateTimer::make('auction.trading_start')
                    ->title('Дата начала торгов')
                    ->format('Y-m-d H:i')
                    ->required(),

                Input::make('auction.starting_price')
                    ->title('Начальная (максимальная) цена, ₽')
                    ->type('number')
                    ->step('0.01')
                    ->required(),

                Input::make('auction.step_percent')
                    ->title('Шаг аукциона, %')
                    ->type('number')
                    ->step('0.01')
                    ->min(0.5)
                    ->max(5)
                    ->value(1.00)
                    ->help('От 0.5% до 5%')
                    ->required(),

                Upload::make('auction.technical_specification')
                    ->title('Техническое задание (PDF)')
                    ->acceptedFiles('.pdf')
                    ->maxFiles(1),

                Select::make('auction.status')
                    ->title('Статус')
                    ->options([
                        'draft' => 'Черновик',
                        'active' => 'Активный',
                        'trading' => 'Торги',
                        'closed' => 'Завершён',
                        'cancelled' => 'Отменён',
                    ])
                    ->required(),
            ]),
        ];
    }

    /**
     * Save auction.
     */
    public function save(Request $request, Auction $auction)
    {
        $data = $request->get('auction');
        
        if (!$auction->exists) {
            $data['number'] = Auction::generateNumber();
            $data['created_by'] = auth()->id();
        }
        
        $auction->fill($data)->save();
        
        Toast::info('Аукцион успешно сохранён.');
        
        return redirect()->route('platform.auctions.list');
    }

    /**
     * Remove auction.
     */
    public function remove(Auction $auction)
    {
        $auction->delete();
        
        Toast::info('Аукцион успешно удалён.');
        
        return redirect()->route('platform.auctions.list');
    }
}