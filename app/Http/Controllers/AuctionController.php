<?php

namespace App\Http\Controllers;

use App\Models\Auction;
use App\Models\AuctionBid;
use App\Models\AuctionInvitation;
use App\Models\Company;
use App\Http\Requests\StoreAuctionRequest;
use App\Http\Requests\UpdateAuctionRequest;
use App\Http\Requests\StoreAuctionBidRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AuctionController extends Controller
{
    /**
     * Display a listing of auctions.
     */
    public function index(Request $request)
    {
        $query = Auction::with(['company.industry', 'creator', 'bids']);
        
        // Фильтр: поиск по названию/номеру
        if ($request->filled('search')) {
            $query->search($request->search);
        }
        
        // Фильтр: статус
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // Фильтр: тип процедуры
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        
        // Сортировка по дате создания (новые первыми)
        $auctions = $query->orderBy('created_at', 'desc')->paginate(20);
        
        return view('auctions.index', compact('auctions'));
    }

    /**
     * Show the form for creating a new auction.
     */
    public function create()
    {
        $this->authorize('create', Auction::class);
        
        // Компании, где текущий пользователь — модератор
        $companies = auth()->user()->moderatedCompanies;
        
        // Все компании для приглашения (кроме своих)
        $allCompanies = Company::where('is_verified', true)
            ->whereNotIn('id', $companies->pluck('id'))
            ->orderBy('name')
            ->get();
        
        return view('auctions.create', compact('companies', 'allCompanies'));
    }

    /**
     * Store a newly created auction in storage.
     */
    public function store(StoreAuctionRequest $request)
    {
        DB::beginTransaction();
        
        try {
            // Создаём аукцион
            $auction = Auction::create([
                'number' => Auction::generateNumber(),
                'title' => $request->title,
                'description' => $request->description,
                'company_id' => $request->company_id,
                'created_by' => auth()->id(),
                'type' => $request->type,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'trading_start' => $request->trading_start,
                'starting_price' => $request->starting_price,
                'step_percent' => $request->step_percent,
                'status' => $request->status ?? 'draft',
            ]);
            
            // Загрузка технического задания
            if ($request->hasFile('technical_specification')) {
                $auction->addMedia($request->file('technical_specification'))
                    ->toMediaCollection('technical_specification');
            }
            
            // Если закрытая процедура — создаём приглашения
            if ($request->type === 'closed' && $request->filled('invited_companies')) {
                foreach ($request->invited_companies as $companyId) {
                    AuctionInvitation::create([
                        'auction_id' => $auction->id,
                        'company_id' => $companyId,
                    ]);
                }
            }
            
            DB::commit();
            
            // Разные сообщения в зависимости от статуса
            if ($auction->status === 'active') {
                return redirect()->route('auctions.show', $auction)
                    ->with('success', 'Аукцион успешно создан и активирован! Номер: ' . $auction->number);
            } else {
                return redirect()->route('auctions.show', $auction)
                    ->with('success', 'Аукцион сохранён как черновик. Номер: ' . $auction->number);
            }
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return back()->withInput()
                ->with('error', 'Ошибка при создании аукциона: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified auction.
     */
    public function show(Auction $auction)
    {
        $this->authorize('view', $auction);
        
        // Eager loading для оптимизации
        $auction->load([
            'company.industry',
            'creator',
            'bids.company',
            'invitations.company'
        ]);
        
        // Проверка: может ли текущий пользователь подать заявку/ставку
        $canBid = auth()->check() && auth()->user()->can('placeBid', $auction);
        
        // Компании текущего пользователя (для выбора в форме)
        $userCompanies = auth()->check() 
            ? auth()->user()->moderatedCompanies()
                ->where('id', '!=', $auction->company_id) // Исключаем организатора
                ->get()
            : collect();
        
        // Проверка: уже подана заявка/ставка от компаний пользователя
        $existingBid = null;
        if ($userCompanies->isNotEmpty()) {
            $existingBid = $auction->bids()
                ->whereIn('company_id', $userCompanies->pluck('id'))
                ->first();
        }
        
        // Текущая минимальная цена
        $currentPrice = $auction->getCurrentPrice();
        
        // Диапазон шага
        $stepRange = $auction->getStepRange();
        
        return view('auctions.show', compact(
            'auction',
            'canBid',
            'userCompanies',
            'existingBid',
            'currentPrice',
            'stepRange'
        ));
    }

    /**
     * Show the form for editing the specified auction.
     */
    public function edit(Auction $auction)
    {
        $this->authorize('update', $auction);
        
        return view('auctions.edit', compact('auction'));
    }

    /**
     * Update the specified auction in storage.
     */
    public function update(UpdateAuctionRequest $request, Auction $auction)
    {
        DB::beginTransaction();
        
        try {
            $auction->update($request->validated());
            
            // Обновление технического задания
            if ($request->hasFile('technical_specification')) {
                $auction->clearMediaCollection('technical_specification');
                $auction->addMedia($request->file('technical_specification'))
                    ->toMediaCollection('technical_specification');
            }
            
            DB::commit();
            
            return redirect()->route('auctions.show', $auction)
                ->with('success', 'Аукцион успешно обновлён.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return back()->withInput()
                ->with('error', 'Ошибка при обновлении аукциона: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified auction from storage.
     */
    public function destroy(Auction $auction)
    {
        $this->authorize('delete', $auction);
        
        $auction->delete();
        
        return redirect()->route('auctions.index')
            ->with('success', 'Аукцион успешно удалён.');
    }

    /**
     * Activate draft auction.
     */
    public function activate(Auction $auction)
    {
        $this->authorize('activate', $auction);
        
        $auction->update(['status' => 'active']);
        
        return redirect()->route('auctions.show', $auction)
            ->with('success', 'Аукцион активирован! Теперь компании могут подавать заявки на участие.');
    }

    /**
     * Store a bid (application or trading bid).
     */
    public function storeBid(StoreAuctionBidRequest $request, Auction $auction)
    {
        DB::beginTransaction();
        
        try {
            $companyId = $request->company_id;
            
            // Проверка: нет ли уже заявки/ставки от этой компании
            $existingBid = $auction->bids()
                ->where('company_id', $companyId)
                ->first();
            
            if ($existingBid && !$auction->isTrading()) {
                return back()->with('error', 'Вы уже подали заявку на участие.');
            }
            
            // Определяем тип ставки
            $isInitialBid = !$auction->isTrading();
            
            // Генерация anonymous_code (если это первая ставка от компании в торгах)
            $anonymousCode = null;
            if ($auction->isTrading()) {
                // Проверяем, есть ли уже код у этой компании
                $firstBid = $auction->bids()
                    ->where('company_id', $companyId)
                    ->first();
                
                $anonymousCode = $firstBid 
                    ? $firstBid->anonymous_code 
                    : Auction::generateAnonymousCode();
            }
            
            // Создаём ставку
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
            
            // Если это ставка в торгах, обновляем last_bid_at
            if (!$isInitialBid) {
                $auction->update(['last_bid_at' => Carbon::now()]);
                
                // TODO: Планируем Job для закрытия через 20 минут
                // \App\Jobs\CloseAuctionJob::dispatch($auction)->delay(Carbon::now()->addMinutes(20));
            }
            
            DB::commit();
            
            if ($isInitialBid) {
                return redirect()->route('auctions.show', $auction)
                    ->with('success', 'Заявка на участие успешно подана!');
            } else {
                return redirect()->route('auctions.show', $auction)
                    ->with('success', 'Ставка принята! Ваш код участника: ' . $anonymousCode);
            }
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return back()->with('error', 'Ошибка при подаче ставки: ' . $e->getMessage());
        }
    }

    /**
     * Show user's auctions (as organizer).
     */
    public function myAuctions()
    {
        $userCompanies = auth()->user()->moderatedCompanies()->pluck('id');
        
        $auctions = Auction::with(['company', 'bids'])
            ->whereIn('company_id', $userCompanies)
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return view('auctions.my-auctions', compact('auctions'));
    }

    /**
     * Show user's bids (as participant).
     */
    public function myBids()
    {
        $userCompanies = auth()->user()->moderatedCompanies()->pluck('id');
        
        $bids = AuctionBid::with(['auction.company', 'company'])
            ->whereIn('company_id', $userCompanies)
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return view('auctions.my-bids', compact('bids'));
    }

    /**
     * Show user's invitations.
     */
    public function myInvitations()
    {
        $userCompanies = auth()->user()->moderatedCompanies()->pluck('id');
        
        $invitations = AuctionInvitation::with(['auction.company', 'company'])
            ->whereIn('company_id', $userCompanies)
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return view('auctions.my-invitations', compact('invitations'));
    }
}