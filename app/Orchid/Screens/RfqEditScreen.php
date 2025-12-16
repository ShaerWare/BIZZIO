<?php

namespace App\Orchid\Screens;

use App\Models\Rfq;
use App\Models\Company;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Fields\Upload;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Alert;
use Orchid\Support\Facades\Layout;

class RfqEditScreen extends Screen
{
    /**
     * @var Rfq
     */
    public $rfq;

    /**
     * Query data.
     *
     * @param Rfq $rfq
     * @return array
     */
    public function query(Rfq $rfq): iterable
    {
        $rfq->load(['company', 'creator']);

        return [
            'rfq' => $rfq,
        ];
    }

    /**
     * Display header name.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return $this->rfq->exists 
            ? 'Редактировать RFQ: ' . $this->rfq->number 
            : 'Создать новый RFQ';
    }

    /**
     * Display header description.
     *
     * @return string|null
     */
    public function description(): ?string
    {
        return $this->rfq->exists 
            ? $this->rfq->title 
            : 'Размещение нового запроса котировок';
    }

    /**
     * Button commands.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            Button::make('Сохранить')
                ->icon('check')
                ->method('createOrUpdate')
                ->canSee(!$this->rfq->exists),

            Button::make('Обновить')
                ->icon('note')
                ->method('createOrUpdate')
                ->canSee($this->rfq->exists),

            Button::make('Удалить')
                ->icon('trash')
                ->method('remove')
                ->canSee($this->rfq->exists && $this->rfq->status === 'draft')
                ->confirm('Вы уверены, что хотите удалить этот RFQ?'),
        ];
    }

    /**
     * Views.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        return [
            Layout::block(Layout::rows([
                Input::make('rfq.number')
                    ->title('Номер RFQ')
                    ->placeholder('Генерируется автоматически')
                    ->disabled()
                    ->canSee($this->rfq->exists),

                Relation::make('rfq.company_id')
                    ->title('Компания-организатор')
                    ->fromModel(Company::class, 'name')
                    ->required()
                    ->help('Выберите компанию-организатора'),

                Input::make('rfq.title')
                    ->title('Название')
                    ->placeholder('Введите название запроса котировок')
                    ->required()
                    ->maxlength(255)
                    ->help('Краткое и понятное название'),

                TextArea::make('rfq.description')
                    ->title('Описание')
                    ->placeholder('Введите описание запроса котировок')
                    ->rows(5)
                    ->help('Подробное описание требований и условий'),

                Select::make('rfq.type')
                    ->title('Тип процедуры')
                    ->options([
                        'open' => 'Открытая (любая компания может подать заявку)',
                        'closed' => 'Закрытая (только приглашённые компании)',
                    ])
                    ->required()
                    ->help('Выберите тип процедуры'),

                Select::make('rfq.status')
                    ->title('Статус')
                    ->options([
                        'draft' => 'Черновик',
                        'active' => 'Активный',
                        'closed' => 'Завершён',
                        'cancelled' => 'Отменён',
                    ])
                    ->required()
                    ->help('Статус запроса котировок'),

            ]))
                ->title('Основная информация')
                ->description('Основные данные о запросе котировок'),

            Layout::block(Layout::rows([
                DateTimer::make('rfq.start_date')
                    ->title('Дата начала приёма заявок')
                    ->required()
                    ->format('Y-m-d H:i')
                    ->help('Дата и время начала приёма заявок'),

                DateTimer::make('rfq.end_date')
                    ->title('Дата окончания приёма заявок')
                    ->required()
                    ->format('Y-m-d H:i')
                    ->help('Дата и время окончания приёма заявок'),
            ]))
                ->title('Сроки')
                ->description('Временные рамки проведения процедуры'),

            Layout::block(Layout::rows([
                Input::make('rfq.weight_price')
                    ->title('Вес критерия "Цена" (%)')
                    ->type('number')
                    ->min(0)
                    ->max(100)
                    ->step(0.01)
                    ->value(50)
                    ->required()
                    ->help('Вес в процентах (0-100)'),

                Input::make('rfq.weight_deadline')
                    ->title('Вес критерия "Срок выполнения" (%)')
                    ->type('number')
                    ->min(0)
                    ->max(100)
                    ->step(0.01)
                    ->value(30)
                    ->required()
                    ->help('Вес в процентах (0-100)'),

                Input::make('rfq.weight_advance')
                    ->title('Вес критерия "Размер аванса" (%)')
                    ->type('number')
                    ->min(0)
                    ->max(100)
                    ->step(0.01)
                    ->value(20)
                    ->required()
                    ->help('Вес в процентах (0-100). Сумма всех весов должна быть равна 100%'),
            ]))
                ->title('Критерии оценки')
                ->description('Веса критериев для оценки заявок (сумма должна быть 100%)'),

            Layout::block(Layout::rows([
                Upload::make('rfq.technical_specification')
                    ->title('Техническое задание (PDF)')
                    ->acceptedFiles('.pdf')
                    ->maxFiles(1)
                    ->help('Загрузите техническое задание в формате PDF (макс. 10 МБ)'),
            ]))
                ->title('Документы')
                ->description('Техническое задание и другие документы'),
        ];
    }

    /**
     * Create or update RFQ.
     *
     * @param Request $request
     * @param Rfq $rfq
     * @return \Illuminate\Http\RedirectResponse
     */
    public function createOrUpdate(Request $request, Rfq $rfq)
    {
        // Валидация
        $request->validate([
            'rfq.company_id' => 'required|exists:companies,id',
            'rfq.title' => 'required|string|max:255',
            'rfq.type' => 'required|in:open,closed',
            'rfq.status' => 'required|in:draft,active,closed,cancelled',
            'rfq.start_date' => 'required|date',
            'rfq.end_date' => 'required|date|after:rfq.start_date',
            'rfq.weight_price' => 'required|numeric|min:0|max:100',
            'rfq.weight_deadline' => 'required|numeric|min:0|max:100',
            'rfq.weight_advance' => 'required|numeric|min:0|max:100',
        ]);

        // Проверка суммы весов
        $totalWeight = $request->input('rfq.weight_price') 
            + $request->input('rfq.weight_deadline') 
            + $request->input('rfq.weight_advance');

        if (abs($totalWeight - 100) > 0.01) {
            Alert::error('Сумма весов критериев должна быть равна 100%');
            return back();
        }

        $rfqData = $request->get('rfq');

        // Генерация номера для нового RFQ
        if (!$rfq->exists) {
            $rfqData['number'] = Rfq::generateNumber();
            $rfqData['created_by'] = auth()->id();
        }

        $rfq->fill($rfqData)->save();

        // Обработка загрузки технического задания
        if ($request->hasFile('rfq.technical_specification')) {
            $rfq->clearMediaCollection('technical_specification');
            $rfq->addMediaFromRequest('rfq.technical_specification')
                ->toMediaCollection('technical_specification');
        }

        Alert::info('RFQ успешно ' . ($rfq->wasRecentlyCreated ? 'создан' : 'обновлён'));

        return redirect()->route('platform.rfqs.list');
    }

    /**
     * Remove RFQ.
     *
     * @param Rfq $rfq
     * @return \Illuminate\Http\RedirectResponse
     */
    public function remove(Rfq $rfq)
    {
        if ($rfq->status !== 'draft') {
            Alert::error('Можно удалить только черновики');
            return back();
        }

        $rfq->delete();

        Alert::info('RFQ успешно удалён');

        return redirect()->route('platform.rfqs.list');
    }
}