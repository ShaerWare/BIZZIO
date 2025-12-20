<?php

namespace App\Http\Controllers;

use App\Models\Rfq;
use App\Models\RfqBid;
use App\Models\Company;
use App\Http\Requests\StoreRfqRequest;
use App\Http\Requests\UpdateRfqRequest;
use App\Http\Requests\StoreBidRequest;
use App\Jobs\CloseRfqJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class RfqController extends Controller
{
     use AuthorizesRequests;
     
     /**
     * Каталог RFQ
     */
    public function index(Request $request)
    {
        $query = Rfq::with(['company', 'creator', 'bids'])
            ->orderBy('created_at', 'desc');

        // Фильтры
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $rfqs = $query->paginate(20);

        return view('rfqs.index', compact('rfqs'));
    }

    /**
     * Форма создания RFQ
     */
    public function create()
    {
        $this->authorize('create', Rfq::class);

        // Компании, где пользователь является модератором
        $companies = auth()->user()->moderatedCompanies;

        // Список всех верифицированных компаний для приглашений (кроме своих)
        $availableCompanies = Company::verified()
            ->whereNotIn('id', $companies->pluck('id'))
            ->orderBy('name')
            ->get();

        return view('rfqs.create', compact('companies', 'availableCompanies'));
    }

    /**
     * Сохранение нового RFQ
     */
    public function store(StoreRfqRequest $request)
    {
        DB::beginTransaction();

        try {
            // Создание RFQ
            $rfq = Rfq::create([
                'number' => Rfq::generateNumber(),
                'title' => $request->title,
                'description' => $request->description,
                'company_id' => $request->company_id,
                'created_by' => auth()->id(),
                'type' => $request->type,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'weight_price' => $request->weight_price,
                'weight_deadline' => $request->weight_deadline,
                'weight_advance' => $request->weight_advance,
                'status' => 'draft',
            ]);

            // Загрузка технического задания (PDF)
            if ($request->hasFile('technical_specification')) {
                $rfq->addMediaFromRequest('technical_specification')
                    ->toMediaCollection('technical_specification');
            }

            // Отправка приглашений (для закрытых процедур)
            if ($request->type === 'closed' && $request->filled('invited_companies')) {
                foreach ($request->invited_companies as $companyId) {
                    $rfq->invitations()->create([
                        'company_id' => $companyId,
                        'invited_by' => auth()->id(),
                        'status' => 'pending',
                    ]);
                }
            }

            // Планирование автозакрытия
            CloseRfqJob::dispatch($rfq)->delay($rfq->end_date);

            DB::commit();

            return redirect()->route('rfqs.show', $rfq)
                ->with('success', 'Запрос котировок создан успешно');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Ошибка при создании: ' . $e->getMessage());
        }
    }

    /**
     * Профиль RFQ
     */
    public function show(Rfq $rfq)
    {
        $this->authorize('view', $rfq);

        $rfq->load([
            'company', 
            'creator', 
            'bids.company', 
            'bids.user',
            'invitations.company',
            'winnerBid.company'
        ]);

        // Проверка: может ли текущий пользователь подать заявку
        $canBid = false;
        if (auth()->check() && $rfq->isActive()) {
            $userCompanies = auth()->user()->moderatedCompanies->pluck('id');
            
            // Проверка: пользователь модератор компании
            if ($userCompanies->isNotEmpty()) {
                // Проверка: компания ещё не подавала заявку
                $alreadyBid = $rfq->bids()->whereIn('company_id', $userCompanies)->exists();
                
                if (!$alreadyBid) {
                    // Для открытых процедур или если есть приглашение
                    $canBid = $rfq->type === 'open' 
                        || $rfq->invitations()->whereIn('company_id', $userCompanies)->exists();
                }
            }
        }

        return view('rfqs.show', compact('rfq', 'canBid'));
    }

    /**
     * Форма редактирования RFQ
     */
    public function edit(Rfq $rfq)
    {
        $this->authorize('update', $rfq);

        return view('rfqs.edit', compact('rfq'));
    }

    /**
     * Обновление RFQ
     */
    public function update(UpdateRfqRequest $request, Rfq $rfq)
    {
        DB::beginTransaction();

        try {
            $rfq->update($request->validated());

            // Обновление технического задания
            if ($request->hasFile('technical_specification')) {
                $rfq->clearMediaCollection('technical_specification');
                $rfq->addMediaFromRequest('technical_specification')
                    ->toMediaCollection('technical_specification');
            }

            DB::commit();

            return redirect()->route('rfqs.show', $rfq)
                ->with('success', 'Запрос котировок обновлён');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Ошибка при обновлении: ' . $e->getMessage());
        }
    }

    /**
     * Удаление RFQ
     */
    public function destroy(Rfq $rfq)
    {
        $this->authorize('delete', $rfq);

        $rfq->delete();

        return redirect()->route('rfqs.index')
            ->with('success', 'Запрос котировок удалён');
    }

    /**
     * Подача заявки на участие
     */
    public function storeBid(StoreBidRequest $request, Rfq $rfq)
    {
        DB::beginTransaction();

        try {
            $bid = RfqBid::create([
                'rfq_id' => $rfq->id,
                'company_id' => $request->company_id,
                'user_id' => auth()->id(),
                'price' => $request->price,
                'deadline' => $request->deadline,
                'advance_percent' => $request->advance_percent,
                'comment' => $request->comment,
                'status' => 'pending',
            ]);

            DB::commit();

            return redirect()->route('rfqs.show', $rfq)
                ->with('success', 'Заявка подана успешно');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Ошибка при подаче заявки: ' . $e->getMessage());
        }
    }

    /**
     * Мои RFQ (организатор)
     */
    public function myRfqs()
    {
        $userCompanies = auth()->user()->moderatedCompanies->pluck('id');

        $rfqs = Rfq::with(['company', 'bids'])
            ->whereIn('company_id', $userCompanies)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('rfqs.my-rfqs', compact('rfqs'));
    }

    /**
     * Мои заявки (участник)
     */
    public function myBids()
    {
        $userCompanies = auth()->user()->moderatedCompanies->pluck('id');

        $bids = RfqBid::with(['rfq.company', 'company'])
            ->whereIn('company_id', $userCompanies)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('rfqs.my-bids', compact('bids'));
    }

    /**
     * Мои приглашения
     */
    public function myInvitations()
    {
        $userCompanies = auth()->user()->moderatedCompanies->pluck('id');

        $invitations = \App\Models\RfqInvitation::with(['rfq.company', 'company'])
            ->whereIn('company_id', $userCompanies)
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('rfqs.my-invitations', compact('invitations'));
    }

    /**
     * Активировать RFQ (перевести из черновика в активный)
     */
    public function activate(Rfq $rfq)
    {
        $this->authorize('update', $rfq);

        if ($rfq->status !== 'draft') {
            return back()->with('error', 'Можно активировать только черновики');
        }

        // Проверка готовности RFQ
        if (!$rfq->hasMedia('technical_specification')) {
            return back()->with('error', 'Загрузите техническое задание перед активацией');
        }

        DB::beginTransaction();

        try {
            // Активируем RFQ
            $rfq->update(['status' => 'active']);

            // Планируем автозакрытие
            CloseRfqJob::dispatch($rfq)->delay($rfq->end_date);

            DB::commit();

            return redirect()->route('rfqs.show', $rfq)
                ->with('success', 'RFQ успешно активирован! Приём заявок открыт.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Ошибка при активации: ' . $e->getMessage());
        }
    }
}