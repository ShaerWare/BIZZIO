<?php

namespace App\Http\Controllers;

use App\Models\Rfq;
use App\Models\Company;
use App\Http\Requests\StoreRfqRequest;
use App\Http\Requests\UpdateRfqRequest;
use App\Http\Requests\StoreBidRequest;
use Illuminate\Http\Request;

class RfqController extends Controller
{
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
        // Компании, где пользователь является модератором
        $companies = auth()->user()->moderatedCompanies;

        // Список всех компаний для приглашений (кроме своих)
        $availableCompanies = Company::whereNotIn('id', $companies->pluck('id'))->get();

        return view('rfqs.create', compact('companies', 'availableCompanies'));
    }

    /**
     * Сохранение нового RFQ
     */
    public function store(StoreRfqRequest $request)
    {
        // Создание RFQ (полная реализация на следующем шаге)
    }

    /**
     * Профиль RFQ
     */
    public function show(Rfq $rfq)
    {
        $rfq->load(['company', 'creator', 'bids.company', 'invitations.company']);

        return view('rfqs.show', compact('rfq'));
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
        // Обновление RFQ (полная реализация на следующем шаге)
    }

    /**
     * Удаление RFQ
     */
    public function destroy(Rfq $rfq)
    {
        $this->authorize('delete', $rfq);

        $rfq->delete();

        return redirect()->route('rfqs.index')->with('success', 'Запрос котировок удалён');
    }

    /**
     * Подача заявки на участие
     */
    public function storeBid(StoreBidRequest $request, Rfq $rfq)
    {
        // Подача заявки (полная реализация на следующем шаге)
    }
}