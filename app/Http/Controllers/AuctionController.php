<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAuctionBidRequest;
use App\Http\Requests\StoreAuctionRequest;
use App\Http\Requests\UpdateAuctionRequest;
use App\Models\Auction;
use App\Models\AuctionBid;
use App\Models\AuctionInvitation;
use App\Models\Company;
use App\Services\AuctionProtocolService;
use App\Traits\HandlesTempUploads;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuctionController extends Controller
{
    use AuthorizesRequests, HandlesTempUploads;

    public function index(Request $request)
    {
        $query = Auction::with(['company.industry', 'creator.badges', 'bids']);

        // Скрываем черновики от посторонних (C3) и закрытые аукционы от неприглашённых (#38)
        if (auth()->check()) {
            $userCompanies = auth()->user()->moderatedCompanies()->pluck('companies.id');
            $query->where(function ($q) use ($userCompanies) {
                $q->where(function ($inner) use ($userCompanies) {
                    // Черновики — только свои
                    $inner->where('status', '!=', 'draft')
                        ->orWhereIn('company_id', $userCompanies);
                })->where(function ($inner) use ($userCompanies) {
                    // #38: Закрытые аукционы — только организатор или приглашённые
                    $inner->where('type', '!=', 'closed')
                        ->orWhereIn('company_id', $userCompanies)
                        ->orWhereHas('invitations', function ($inv) use ($userCompanies) {
                            $inv->whereIn('company_id', $userCompanies);
                        });
                });
            });
        } else {
            $query->where('status', '!=', 'draft')
                ->where('type', '!=', 'closed');
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('status') && $request->status !== 'draft') {
            // Не позволяем фильтровать по draft напрямую (только свои)
            $query->where('status', $request->status);
        } elseif ($request->filled('status') && $request->status === 'draft' && auth()->check()) {
            // Для draft показываем только свои
            $userCompanies = auth()->user()->moderatedCompanies()->pluck('companies.id');
            $query->where('status', 'draft')->whereIn('company_id', $userCompanies);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $auctions = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('auctions.index', compact('auctions'));
    }

    public function create()
    {
        $this->authorize('create', Auction::class);

        $companies = auth()->user()->moderatedCompanies;

        return view('auctions.create', compact('companies'));
    }

    public function store(StoreAuctionRequest $request)
    {
        DB::beginTransaction();

        try {
            $auction = Auction::create([
                'number' => Auction::generateNumber(),
                'title' => $request->title,
                'description' => $request->description,
                'company_id' => $request->company_id,
                'created_by' => auth()->id(),
                'type' => $request->type,
                'currency' => $request->currency ?? 'RUB',
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'trading_start' => $request->trading_start,
                'starting_price' => $request->starting_price,
                'step_percent' => 2.5, // A4: Фиксированный диапазон 0.5-5%, среднее значение для совместимости
                'status' => $request->status ?? 'draft',
                'is_results_hidden' => $request->boolean('is_results_hidden'),
            ]);

            // #185 Загрузка конкурсной документации (Извещение / ТЗ / Проект договора / Прочие) со сжатием.
            app(\App\Services\ProcurementDocumentsService::class)->attachFromRequest($auction, $request);

            if ($request->type === 'closed' && $request->filled('invited_companies')) {
                foreach ($request->invited_companies as $companyId) {
                    AuctionInvitation::create([
                        'auction_id' => $auction->id,
                        'company_id' => $companyId,
                    ]);
                }
            }

            DB::commit();

            if ($auction->status === 'active') {
                return redirect()->route('auctions.show', $auction)
                    ->with('success', 'Аукцион успешно создан и активирован! Номер: '.$auction->number);
            } else {
                return redirect()->route('auctions.show', $auction)
                    ->with('success', 'Аукцион сохранён как черновик. Номер: '.$auction->number);
            }

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()
                ->with('error', 'Ошибка при создании аукциона: '.$e->getMessage());
        }
    }

    public function show(Auction $auction)
    {
        $this->authorize('view', $auction);

        $auction->load([
            'company.industry',
            'creator.badges',
            'bids.company',
            'bids.user.badges',
            'invitations.company',
        ]);

        // Сначала получаем компании пользователя
        $userCompanies = auth()->check()
            ? auth()->user()->moderatedCompanies()
                ->where('companies.id', '!=', $auction->company_id)
                ->get()
            : collect();

        // Проверяем существующую заявку
        $existingBid = null;
        if ($userCompanies->isNotEmpty()) {
            $existingBid = $auction->bids()
                ->whereIn('company_id', $userCompanies->pluck('id'))
                ->first();
        }

        // Вычисляем $canBid на основе всех условий
        $canBid = false;

        // #182: Подавать заявки/ставки могут только верифицированные компании
        $biddableCompanies = $userCompanies->where('is_verified', true);

        if (auth()->check() && $biddableCompanies->isNotEmpty()) {
            // 1. Проверка статуса аукциона
            $isAcceptingOrTrading = $auction->isAcceptingApplications() || $auction->isTrading();

            if ($isAcceptingOrTrading) {
                // 2. Для закрытых аукционов проверяем приглашение
                if ($auction->type === 'closed') {
                    $isInvited = $auction->invitations()
                        ->whereIn('company_id', $biddableCompanies->pluck('id'))
                        ->exists();

                    $canBid = $isInvited;
                } else {
                    // 3. Для открытых аукционов — можно всем модераторам
                    $canBid = true;
                }

                // 4. Для active: блокируем повторную заявку; для trading: разрешаем только подавшему заявку
                if ($auction->isTrading()) {
                    // #119: Только пользователь, подавший заявку (initial bid), может делать ставки
                    $initialBid = $auction->bids()
                        ->where('type', 'initial')
                        ->where('user_id', auth()->id())
                        ->whereIn('company_id', $biddableCompanies->pluck('id'))
                        ->first();
                    $canBid = (bool) $initialBid;
                    $existingBid = $initialBid;
                } elseif ($existingBid) {
                    $canBid = false;
                }
            }
        }

        $currentPrice = $auction->getCurrentPrice();
        $stepRange = $auction->getStepRange();

        // #115: Определяем, может ли пользователь видеть результаты
        $canSeeResults = true;
        if ($auction->is_results_hidden && in_array($auction->status, ['closed', 'cancelled'])) {
            $isManager = auth()->check() && $auction->canManage(auth()->user());
            $isParticipant = auth()->check() && $auction->bids->pluck('company_id')
                ->intersect($userCompanies->pluck('id'))->isNotEmpty();
            $canSeeResults = $isManager || $isParticipant;
        }

        // #119: При торгах показываем только компанию, от которой подана заявка текущим пользователем
        // #182: в форме заявки — только верифицированные компании
        $bidCompanies = $biddableCompanies;
        if ($auction->isTrading() && $canBid && $existingBid) {
            $bidCompanies = $biddableCompanies->where('id', $existingBid->company_id)->values();
        }

        return view('auctions.show', compact(
            'auction',
            'canBid',
            'userCompanies',
            'bidCompanies',
            'existingBid',
            'currentPrice',
            'stepRange',
            'canSeeResults'
        ));
    }

    public function edit(Auction $auction)
    {
        $this->authorize('update', $auction);

        return view('auctions.edit', compact('auction'));
    }

    public function update(UpdateAuctionRequest $request, Auction $auction)
    {
        DB::beginTransaction();

        try {
            $auction->update($request->validated());

            // #185 Обновление конкурсной документации (загруженные файлы заменяют/дополняют коллекции).
            app(\App\Services\ProcurementDocumentsService::class)->attachFromRequest($auction, $request);

            DB::commit();

            return redirect()->route('auctions.show', $auction)
                ->with('success', 'Аукцион успешно обновлён.');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()
                ->with('error', 'Ошибка при обновлении аукциона: '.$e->getMessage());
        }
    }

    public function destroy(Auction $auction)
    {
        $this->authorize('delete', $auction);

        $auction->delete();

        return redirect()->route('auctions.index')
            ->with('success', 'Аукцион успешно удалён.');
    }

    public function activate(Auction $auction)
    {
        $this->authorize('activate', $auction);

        $auction->update(['status' => 'active']);

        return redirect()->route('auctions.show', $auction)
            ->with('success', 'Аукцион активирован! Теперь компании могут подавать заявки на участие.');
    }

    /**
     * #148: отмена аукциона организатором (до начала торгов) с указанием причины.
     */
    public function cancel(Request $request, Auction $auction)
    {
        $this->authorize('cancel', $auction);

        $validated = $request->validate([
            'cancellation_reason' => ['required', 'string', 'max:1000'],
        ], [
            'cancellation_reason.required' => 'Укажите причину отмены.',
        ]);

        $auction->update([
            'status' => 'cancelled',
            'cancellation_reason' => $validated['cancellation_reason'],
        ]);

        return redirect()->route('auctions.show', $auction)
            ->with('success', 'Аукцион отменён.');
    }

    public function storeBid(StoreAuctionBidRequest $request, Auction $auction)
    {
        DB::beginTransaction();

        try {
            $companyId = $request->company_id;
            $company = Company::find($companyId);

            // #182: Заявку/ставку можно подать только от своей верифицированной компании
            if (! $company || ! $company->isModerator(auth()->user())) {
                DB::rollBack();

                return back()->withInput()->with('error', 'Вы не можете участвовать от имени этой компании.');
            }

            if (! $company->is_verified) {
                DB::rollBack();

                return back()->withInput()->with('error', 'Участвовать в аукционе могут только верифицированные компании. Пройдите верификацию компании.');
            }

            if ($company->id === $auction->company_id) {
                DB::rollBack();

                return back()->withInput()->with('error', 'Организатор не может участвовать в собственном аукционе.');
            }

            // Аукцион должен принимать заявки или идти торги
            if (! $auction->isAcceptingApplications() && ! $auction->isTrading()) {
                DB::rollBack();

                return back()->withInput()->with('error', 'Аукцион не принимает заявки.');
            }

            // Для закрытого аукциона компания должна быть приглашена
            if ($auction->type === 'closed'
                && ! $auction->invitations()->where('company_id', $companyId)->exists()) {
                DB::rollBack();

                return back()->withInput()->with('error', 'Ваша компания не приглашена к участию в этом аукционе.');
            }

            // Проверка существующей заявки
            $existingBid = $auction->bids()
                ->where('company_id', $companyId)
                ->first();

            // Если заявка уже есть и это НЕ торги — запретить
            if ($existingBid && ! $auction->isTrading()) {
                DB::rollBack();

                return back()->with('error', 'Вы уже подали заявку на участие в этом аукционе.');
            }

            // #119: В режиме торгов — ставку может делать только пользователь, подавший заявку
            if ($auction->isTrading()) {
                $initialBid = $auction->bids()
                    ->where('type', 'initial')
                    ->where('company_id', $companyId)
                    ->first();

                if (! $initialBid || $initialBid->user_id !== auth()->id()) {
                    DB::rollBack();

                    return back()->with('error', 'Ставку может делать только сотрудник, подавший заявку на участие.');
                }
            }

            // Определяем тип заявки
            $isInitialBid = ! $auction->isTrading();

            // A6: Запретить две ставки подряд от одного участника (только в режиме торгов)
            if ($auction->isTrading()) {
                $lastBid = $auction->tradingBids()->first(); // Последняя ставка (сортировка по desc)
                if ($lastBid && $lastBid->company_id == $companyId) {
                    DB::rollBack();

                    return back()->with('error', 'Нельзя делать две ставки подряд. Дождитесь ставки другого участника.');
                }
            }

            // Генерация анонимного кода для торгов
            $anonymousCode = null;
            if ($auction->isTrading()) {
                // Если это первая ставка от компании — генерируем код
                // Если компания уже ставила — используем существующий код
                $firstBid = $auction->bids()
                    ->where('company_id', $companyId)
                    ->first();

                $anonymousCode = $firstBid
                    ? $firstBid->anonymous_code
                    : Auction::generateAnonymousCode();
            }

            // Создание заявки/ставки
            $bid = AuctionBid::create([
                'auction_id' => $auction->id,
                'company_id' => $companyId,
                'user_id' => auth()->id(),
                'price' => $request->price ?? $auction->starting_price,
                'anonymous_code' => $anonymousCode,
                'comment' => $request->comment,
                'type' => $isInitialBid ? 'initial' : 'bid',
                'status' => 'pending',
            ]);

            // #110: При подаче заявки (initial bid) обновляем статус приглашения на accepted
            if ($isInitialBid) {
                $auction->invitations()
                    ->where('company_id', $companyId)
                    ->where('status', 'pending')
                    ->update(['status' => 'accepted']);
            }

            // Обновление времени последней ставки (для торгов)
            if (! $isInitialBid) {
                $auction->update(['last_bid_at' => Carbon::now()]);
            }

            DB::commit();

            // Редирект с соответствующим сообщением
            if ($isInitialBid) {
                return redirect()
                    ->route('auctions.show', $auction)
                    ->with('success', 'Заявка на участие успешно подана!');
            } else {
                return redirect()
                    ->route('auctions.show', $auction)
                    ->with('success', 'Ставка принята! Ваш код участника: '.$anonymousCode);
            }

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Ошибка при подаче заявки/ставки', [
                'auction_id' => $auction->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->with('error', 'Ошибка при подаче заявки: '.$e->getMessage())
                ->withInput();
        }
    }

    public function myAuctions()
    {
        $userCompanies = auth()->user()->moderatedCompanies()->pluck('companies.id'); // ⚠️ ИСПРАВЛЕНО

        $auctions = Auction::with(['company', 'bids'])
            ->where(function ($query) use ($userCompanies) {
                $query->whereIn('company_id', $userCompanies)
                    ->orWhere('created_by', auth()->id());
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('auctions.my-auctions', compact('auctions'));
    }

    public function myBids()
    {
        $userCompanies = auth()->user()->moderatedCompanies()->pluck('companies.id'); // ⚠️ ИСПРАВЛЕНО

        $bids = AuctionBid::with(['auction.company', 'company'])
            ->whereIn('company_id', $userCompanies)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('auctions.my-bids', compact('bids'));
    }

    public function myInvitations()
    {
        $userCompanies = auth()->user()->moderatedCompanies()->pluck('companies.id'); // ⚠️ ИСПРАВЛЕНО

        $invitations = AuctionInvitation::with(['auction.company', 'company'])
            ->whereIn('company_id', $userCompanies)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('auctions.my-invitations', compact('invitations'));
    }

    public function getState(Auction $auction)
    {
        $this->authorize('view', $auction);

        if (! $auction->isTrading()) {
            return response()->json([
                'status' => 'not_trading',
                'message' => 'Аукцион не находится в режиме торгов.',
            ], 400);
        }

        // #179 Коммерческий аукцион отдаёт расширенное состояние (3 критерия).
        if ($auction->isCommercial()) {
            return response()->json($this->commercialState($auction));
        }

        $userCompanies = auth()->check()
            ? auth()->user()->moderatedCompanies()->pluck('companies.id')->toArray() // ⚠️ ИСПРАВЛЕНО
            : [];

        $bids = $auction->tradingBids()
            ->with('company:id,name')
            ->get()
            ->map(function ($bid) use ($auction, $userCompanies) {
                $canSeeCompany = auth()->check() && $auction->canManage(auth()->user());

                return [
                    'id' => $bid->id,
                    'anonymous_code' => $bid->anonymous_code,
                    'company_name' => $canSeeCompany ? $bid->company->name : null,
                    'price' => number_format($bid->price, 2, '.', ''),
                    'price_formatted' => number_format($bid->price, 2, '.', ' ').' '.$auction->currency_symbol,
                    'created_at' => $bid->created_at->format('H:i:s'),
                    'is_mine' => in_array($bid->company_id, $userCompanies),
                ];
            });

        $currentPrice = $auction->getCurrentPrice();

        $timeRemaining = null;
        if ($auction->last_bid_at) {
            $closingTime = Carbon::parse($auction->last_bid_at)->addMinutes(20);
            $timeRemaining = $closingTime->diffInSeconds(Carbon::now(), false);

            if ($timeRemaining < 0) {
                $timeRemaining = 0;
            }
        }

        return response()->json([
            'status' => 'trading',
            'auction_status' => $auction->status,
            'current_price' => number_format($currentPrice, 2, '.', ''),
            'current_price_formatted' => number_format($currentPrice, 2, '.', ' ').' '.$auction->currency_symbol,
            'bids_count' => $bids->count(),
            'bids' => $bids,
            'time_remaining' => $timeRemaining,
            'last_updated' => Carbon::now()->toIso8601String(),
        ]);
    }

    /**
     * #179 Состояние коммерческого аукциона для long-polling (3 критерия).
     *
     * @return array<string, mixed>
     */
    private function commercialState(Auction $auction): array
    {
        $scoring = app(\App\Services\CommercialAuctionScoringService::class);

        $userCompanies = auth()->check()
            ? auth()->user()->moderatedCompanies()->pluck('companies.id')->toArray()
            : [];

        $canSeeCompany = auth()->check() && $auction->canManage(auth()->user());

        $mapOffer = function (AuctionBid $offer) use ($auction, $userCompanies, $canSeeCompany) {
            return [
                'id' => $offer->id,
                'anonymous_code' => $offer->anonymous_code,
                'company_name' => $canSeeCompany ? $offer->company->name : null,
                'price' => (float) $offer->price,
                'price_formatted' => number_format((float) $offer->price, 2, '.', ' ').' '.$auction->currency_symbol,
                'deadline' => (int) $offer->deadline,
                'advance' => (float) $offer->advance_percent,
                'total_score' => (float) $offer->total_score,
                'time' => optional($offer->became_best_at ?? $offer->created_at)->format('H:i:s'),
                'is_mine' => in_array($offer->company_id, $userCompanies),
            ];
        };

        $history = $auction->offerBids()->with('company:id,name')->get()->map($mapOffer)->values();

        $best = $auction->bestBid;

        // #179 Торги закрываются через 20 мин после последнего предложения (как в обычном аукционе).
        $timeRemaining = null;
        if ($auction->last_bid_at) {
            $closingTime = Carbon::parse($auction->last_bid_at)->addMinutes(20);
            $timeRemaining = max(0, Carbon::now()->diffInSeconds($closingTime, false));
        }

        // #198 Количество компаний-участников, сделавших ставку (уникальные компании среди предложений).
        $participantsCount = $auction->offerBids()->pluck('company_id')->unique()->count();

        return [
            'status' => 'trading',
            'auction_status' => $auction->status,
            'procedure' => 'commercial',
            'currency_symbol' => $auction->currency_symbol,
            'nmc' => (float) $auction->starting_price,
            'weights' => $scoring->weights($auction),
            'refs' => [
                'max_deadline' => (int) $auction->max_deadline,
                'max_advance' => (float) $auction->max_advance,
            ],
            'steps' => [
                'price' => (float) $auction->step_price,
                'deadline' => (int) $auction->step_deadline,
                'advance' => (float) $auction->step_advance,
            ],
            'best_offer' => $best ? $mapOffer($best->loadMissing('company:id,name')) : null,
            'best_offer_history' => $history,
            'offers_count' => $history->count(),
            'participants_count' => $participantsCount,
            'time_remaining' => $timeRemaining,
            'last_updated' => Carbon::now()->toIso8601String(),
        ];
    }

    /**
     * #179 Подача предложения в коммерческом аукционе (этап 2).
     */
    public function storeOffer(
        \App\Http\Requests\StoreCommercialOfferRequest $request,
        Auction $auction,
        \App\Services\CommercialAuctionScoringService $scoring
    ) {
        if (! $auction->isCommercial() || ! $auction->isTrading()) {
            return back()->withInput()->with('error', 'Коммерческий аукцион не находится в режиме торгов.');
        }

        $company = Company::find($request->company_id);

        // Право участия: модератор своей верифицированной компании, не организатор, приглашён.
        if (! $company || ! $company->isModerator(auth()->user())) {
            return back()->withInput()->with('error', 'Вы не можете участвовать от имени этой компании.');
        }
        if (! $company->is_verified) {
            return back()->withInput()->with('error', 'Участвовать могут только верифицированные компании.');
        }
        if ($company->id === $auction->company_id) {
            return back()->withInput()->with('error', 'Организатор не может участвовать в собственном аукционе.');
        }
        if (! $auction->invitations()->where('company_id', $company->id)->exists()) {
            return back()->withInput()->with('error', 'Ваша компания не является участником этого аукциона.');
        }

        $price = (float) $request->price;
        $deadline = (int) $request->deadline;
        $advance = (float) $request->advance_percent;

        // Цена не может превышать НМЦ (среднюю цену этапа 1, #202).
        if ($price > (float) $auction->starting_price) {
            return back()->withInput()->with('error', 'Цена не может превышать начальную максимальную цену.');
        }

        // #210 Срок и аванс не могут превышать заданные организатором максимумы (референсы нормировки).
        if ($auction->max_deadline && $deadline > (int) $auction->max_deadline) {
            return back()->withInput()->with('error', 'Срок не может превышать максимальный срок ('.(int) $auction->max_deadline.' дн.).');
        }
        if ($auction->max_advance && $advance > (float) $auction->max_advance) {
            return back()->withInput()->with('error', 'Аванс не может превышать максимальный размер ('.rtrim(rtrim(number_format((float) $auction->max_advance, 2, '.', ''), '0'), '.').'%).');
        }

        try {
            $result = DB::transaction(function () use ($auction, $company, $price, $deadline, $advance, $scoring) {
                // Блокируем строку аукциона — сериализуем одновременные предложения.
                $locked = Auction::whereKey($auction->id)->lockForUpdate()->first();

                // #210 Референсы нормировки (max_deadline/max_advance) заданы организатором на этапе 1 —
                // не меняются в ходе торгов. Строгое превосходство над текущим лидером (перечитан под локом).
                if (! $scoring->wouldBeat($locked, $price, $deadline, $advance)) {
                    $analysis = $scoring->analyze($locked, $price, $deadline, $advance);

                    return ['ok' => false, 'deficit' => $analysis['deficit']];
                }

                // Код участника: переиспользуем существующий код компании либо генерируем.
                $prior = $locked->bids()->where('company_id', $company->id)->first();
                $code = $prior?->anonymous_code ?? Auction::generateAnonymousCode();
                $isBase = $prior === null;

                $offer = new AuctionBid([
                    'auction_id' => $locked->id,
                    'company_id' => $company->id,
                    'user_id' => auth()->id(),
                    'price' => $price,
                    'deadline' => $deadline,
                    'advance_percent' => $advance,
                    'anonymous_code' => $code,
                    'type' => 'offer',
                    'status' => 'pending',
                    'is_base' => $isBase,
                    'became_best_at' => now(),
                ]);
                $scoring->fillScores($locked, $offer);
                $offer->save();

                $locked->update([
                    'best_bid_id' => $offer->id,
                    'last_bid_at' => now(),
                ]);

                return ['ok' => true, 'code' => $code];
            });
        } catch (\Throwable $e) {
            \Log::error('Ошибка подачи коммерческого предложения', [
                'auction_id' => $auction->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', 'Ошибка при подаче предложения: '.$e->getMessage());
        }

        if (! $result['ok']) {
            return back()->withInput()->with('error',
                'Предложение не принято: до лучшего предложения не хватает '.number_format($result['deficit'], 2, '.', ' ').' баллов.');
        }

        return redirect()->route('auctions.show', $auction)
            ->with('success', 'Предложение принято! Ваш код участника: '.$result['code']);
    }

    /**
     * Генерация протокола аукциона (вручную)
     */
    public function generateProtocol(Auction $auction, AuctionProtocolService $protocolService)
    {
        $this->authorize('generateProtocol', $auction);

        // Генерируем протокол
        $filename = $protocolService->generate($auction);

        if ($filename) {
            return back()->with('success', 'Протокол успешно сгенерирован: '.$filename);
        } else {
            return back()->with('error', 'Ошибка при генерации протокола. Проверьте логи.');
        }
    }
}
