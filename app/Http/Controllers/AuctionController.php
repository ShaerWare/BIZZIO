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
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class AuctionController extends Controller
{
    use AuthorizesRequests;
    
    public function index(Request $request)
    {
        $query = Auction::with(['company.industry', 'creator', 'bids']);
        
        if ($request->filled('search')) {
            $query->search($request->search);
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
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
        
        $allCompanies = Company::where('is_verified', true)
            ->whereNotIn('id', $companies->pluck('id'))
            ->orderBy('name')
            ->get();
        
        return view('auctions.create', compact('companies', 'allCompanies'));
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
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'trading_start' => $request->trading_start,
                'starting_price' => $request->starting_price,
                'step_percent' => $request->step_percent,
                'status' => $request->status ?? 'draft',
            ]);
            
            if ($request->hasFile('technical_specification')) {
                $auction->addMedia($request->file('technical_specification'))
                    ->toMediaCollection('technical_specification');
            }
            
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

    public function show(Auction $auction)
    {
        $this->authorize('view', $auction);
        
        $auction->load([
            'company.industry',
            'creator',
            'bids.company',
            'invitations.company'
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
        
        if (auth()->check() && $userCompanies->isNotEmpty()) {
            // 1. Проверка статуса аукциона
            $isAcceptingOrTrading = $auction->isAcceptingApplications() || $auction->isTrading();
            
            if ($isAcceptingOrTrading) {
                // 2. Для закрытых аукционов проверяем приглашение
                if ($auction->type === 'closed') {
                    $isInvited = $auction->invitations()
                        ->whereIn('company_id', $userCompanies->pluck('id'))
                        ->exists();
                    
                    $canBid = $isInvited;
                } else {
                    // 3. Для открытых аукционов — можно всем модераторам
                    $canBid = true;
                }
                
                // 4. Если уже есть заявка (для статуса 'active'), блокируем
                if ($existingBid && !$auction->isTrading()) {
                    $canBid = false;
                }
            }
        }
        
        $currentPrice = $auction->getCurrentPrice();
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

public function storeBid(StoreAuctionBidRequest $request, Auction $auction)
{
    DB::beginTransaction();
    
    try {
        $companyId = $request->company_id;
        
        // Проверка существующей заявки
        $existingBid = $auction->bids()
            ->where('company_id', $companyId)
            ->first();
        
        // Если заявка уже есть и это НЕ торги — запретить
        if ($existingBid && !$auction->isTrading()) {
            return back()->with('error', 'Вы уже подали заявку на участие в этом аукционе.');
        }
        
        // Определяем тип заявки
        $isInitialBid = !$auction->isTrading();
        
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
        
        // Обновление времени последней ставки (для торгов)
        if (!$isInitialBid) {
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
                ->with('success', 'Ставка принята! Ваш код участника: ' . $anonymousCode);
        }
        
    } catch (\Exception $e) {
        DB::rollBack();
        
        \Log::error('Ошибка при подаче заявки/ставки', [
            'auction_id' => $auction->id,
            'user_id' => auth()->id(),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return back()
            ->with('error', 'Ошибка при подаче заявки: ' . $e->getMessage())
            ->withInput();
    }
}

    public function myAuctions()
{
    $userCompanies = auth()->user()->moderatedCompanies()->pluck('companies.id'); // ⚠️ ИСПРАВЛЕНО
    
    $auctions = Auction::with(['company', 'bids'])
        ->where(function($query) use ($userCompanies) {
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
    
    if (!$auction->isTrading()) {
        return response()->json([
            'status' => 'not_trading',
            'message' => 'Аукцион не находится в режиме торгов.',
        ], 400);
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
                'price_formatted' => number_format($bid->price, 2, '.', ' ') . ' ₽',
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
        'current_price_formatted' => number_format($currentPrice, 2, '.', ' ') . ' ₽',
        'bids_count' => $bids->count(),
        'bids' => $bids,
        'time_remaining' => $timeRemaining,
        'last_updated' => Carbon::now()->toIso8601String(),
    ]);
}
}