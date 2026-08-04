<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRfqRequest;
use App\Http\Requests\UpdateRfqRequest;
use App\Jobs\CloseRfqJob;
use App\Models\Company;
use App\Models\Rfq;
use App\Models\RfqBid;
use App\Models\RfqInvitation;
use App\Traits\HandlesTempUploads;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RfqController extends Controller
{
    use AuthorizesRequests, HandlesTempUploads;

    /**
     * Каталог RFQ
     */
    public function index(Request $request)
    {
        $query = Rfq::with(['company', 'creator.badges', 'bids', 'linkedAuction'])
            ->orderBy('created_at', 'desc');

        // Скрываем черновики от посторонних (C3)
        // Черновики видны только модераторам компании-организатора
        if (auth()->check()) {
            $userCompanies = auth()->user()->moderatedCompanies()->pluck('companies.id');
            $query->where(function ($q) use ($userCompanies) {
                $q->where('status', '!=', 'draft')
                    ->orWhereIn('company_id', $userCompanies);
            });
        } else {
            $query->where('status', '!=', 'draft');
        }

        // Фильтры
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
            $procedure = $request->input('procedure', 'standard') === 'commercial'
                ? Rfq::PROCEDURE_COMMERCIAL
                : Rfq::PROCEDURE_STANDARD;

            $attributes = [
                'number' => Rfq::generateNumber(),
                'title' => $request->title,
                'description' => $request->description,
                'company_id' => $request->company_id,
                'created_by' => auth()->id(),
                'type' => $request->type,
                'procedure' => $procedure,
                'currency' => $request->currency ?? 'RUB',
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'weight_price' => $request->weight_price,
                'weight_deadline' => $request->weight_deadline,
                'weight_advance' => $request->weight_advance,
                'status' => $request->status ?? 'draft', // ✅ ИСПОЛЬЗУЕМ СТАТУС ИЗ ФОРМЫ
                // #237 У коммерческого аукциона результаты скрыты всегда (галочки в форме нет):
                // их видят только организатор и участники процедуры.
                'is_results_hidden' => $procedure === Rfq::PROCEDURE_COMMERCIAL || $request->boolean('is_results_hidden'),
            ];

            // #179 Параметры этапа 2 для коммерческого аукциона.
            // trading_end не задаётся (торги закрываются через 20 мин после последнего предложения).
            // #210 max_deadline/max_advance задаёт организатор — референсы нормировки (100% шкалы) этапа 2.
            if ($procedure === Rfq::PROCEDURE_COMMERCIAL) {
                $attributes += [
                    'trading_start' => $request->trading_start,
                    'step_price' => $request->step_price,
                    'step_deadline' => $request->step_deadline,
                    'step_advance' => $request->step_advance,
                    'max_deadline' => $request->max_deadline,
                    'max_advance' => $request->max_advance,
                ];
            }

            // Создание RFQ
            $rfq = Rfq::create($attributes);

            // #185 Загрузка конкурсной документации (Извещение / ТЗ / Проект договора / Прочие) со сжатием.
            app(\App\Services\ProcurementDocumentsService::class)->attachFromRequest($rfq, $request);

            // T8: Отправка приглашений (для любого типа процедуры)
            if ($request->filled('invited_companies')) {
                foreach ($request->invited_companies as $companyId) {
                    $rfq->invitations()->create([
                        'company_id' => $companyId,
                        'invited_by' => auth()->id(),
                        'status' => 'pending',
                    ]);

                    $company = Company::find($companyId);
                    if ($company) {
                        \App\Events\TenderInvitationSent::dispatch($rfq, $company, 'rfq');
                    }
                }
            }

            // ✅ ПЛАНИРОВАНИЕ АВТОЗАКРЫТИЯ ТОЛЬКО ЕСЛИ СТАТУС = ACTIVE
            if ($rfq->status === 'active') {
                CloseRfqJob::dispatch($rfq)->delay($rfq->end_date);
            }

            DB::commit();

            // ✅ РАЗНЫЕ СООБЩЕНИЯ В ЗАВИСИМОСТИ ОТ СТАТУСА
            $message = $rfq->status === 'active'
                ? 'Запрос цен создан и активирован. Приём заявок открыт!'
                : 'Запрос цен создан как черновик. Активируйте его для начала приёма заявок.';

            return redirect()->route('rfqs.show', $rfq)
                ->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Ошибка при создании: '.$e->getMessage());
        }
    }

    /**
     * Display the specified RFQ.
     */
    public function show(Rfq $rfq)
    {
        // Eager loading
        $rfq->load([
            'company.industry',
            'creator.badges',
            'bids.company',
            'bids.user.badges',
            'invitations.company',
            'linkedAuction',
        ]);

        // Проверка доступа к закрытым RFQ
        $canView = $rfq->type === 'open' ||
                   (auth()->check() && (
                       $rfq->company->isModerator(auth()->user()) ||
                       $rfq->invitations()->whereIn('company_id', auth()->user()->moderatedCompanies->pluck('id'))->exists()
                   ));

        if (! $canView) {
            abort(403, 'У вас нет доступа к этому запросу цен.');
        }

        // Проверка: может ли пользователь подать заявку
        $canBid = false;
        $userCompanies = auth()->check() ? auth()->user()->moderatedCompanies : collect();
        // #182: Подавать заявки могут только верифицированные компании
        $biddableCompanies = $userCompanies->where('is_verified', true);
        $alreadyBid = false;

        if ($rfq->isActive() && ! $rfq->isExpired()) {
            if ($rfq->type === 'open') {
                // Открытая процедура: любая компания пользователя (кроме организатора)
                foreach ($biddableCompanies as $company) {
                    if ($company->id !== $rfq->company_id) {
                        // Проверяем, не подана ли уже заявка от этой компании
                        $bidExists = $rfq->bids()->where('company_id', $company->id)->exists();
                        if (! $bidExists) {
                            $canBid = true;
                            break;
                        } else {
                            $alreadyBid = true;
                        }
                    }
                }
            } else {
                // Закрытая процедура: только приглашённые компании
                $invitedCompanyIds = $rfq->invitations()
                    ->whereIn('company_id', $biddableCompanies->pluck('id'))
                    ->pluck('company_id')
                    ->toArray();

                foreach ($invitedCompanyIds as $companyId) {
                    // Проверяем, не подана ли уже заявка от этой компании
                    $bidExists = $rfq->bids()->where('company_id', $companyId)->exists();
                    if (! $bidExists) {
                        $canBid = true;
                        break;
                    } else {
                        $alreadyBid = true;
                    }
                }
            }
        }

        // Компании пользователя для dropdown в форме заявки
        $availableCompanies = collect();
        if ($canBid) {
            if ($rfq->type === 'open') {
                $availableCompanies = $biddableCompanies->filter(function ($company) use ($rfq) {
                    return $company->id !== $rfq->company_id &&
                           ! $rfq->bids()->where('company_id', $company->id)->exists();
                });
            } else {
                $invitedIds = $rfq->invitations()
                    ->whereIn('company_id', $biddableCompanies->pluck('id'))
                    ->pluck('company_id')
                    ->toArray();

                $availableCompanies = $biddableCompanies->filter(function ($company) use ($invitedIds, $rfq) {
                    return in_array($company->id, $invitedIds) &&
                           ! $rfq->bids()->where('company_id', $company->id)->exists();
                });
            }
        }

        // #115: Определяем, может ли пользователь видеть результаты
        $canSeeResults = true;
        if ($rfq->is_results_hidden && $rfq->status === 'closed') {
            $isManager = auth()->check() && $rfq->canManage(auth()->user());
            $isParticipant = auth()->check() && $rfq->bids->pluck('company_id')
                ->intersect($userCompanies->pluck('id'))->isNotEmpty();
            $canSeeResults = $isManager || $isParticipant;
        }

        // #174: Скачивание протокола доступно организатору и участникам (подавшим заявку).
        // Считаем по реальным компаниям пользователя, т.к. $availableCompanies пуст у закрытого RFQ.
        $isProtocolParticipant = auth()->check() && $rfq->bids->pluck('company_id')
            ->intersect($userCompanies->pluck('id'))->isNotEmpty();
        $canDownloadProtocol = (auth()->check() && $rfq->canManage(auth()->user())) || $isProtocolParticipant;

        return view('rfqs.show', compact('rfq', 'canBid', 'alreadyBid', 'availableCompanies', 'canSeeResults', 'canDownloadProtocol'));
    }

    /**
     * #205 Лёгкий статус этапа 2: запущен ли связанный коммерческий аукцион.
     * Используется JS-поллингом на странице этапа 1 для авто-перехода к торгам.
     */
    public function stage2Status(Rfq $rfq)
    {
        $launched = $rfq->isCommercial() && $rfq->linked_auction_id !== null;

        return response()->json([
            'launched' => $launched,
            'url' => $launched ? route('auctions.show', $rfq->linked_auction_id) : null,
        ]);
    }

    /**
     * Форма редактирования RFQ
     */
    public function edit(Rfq $rfq)
    {
        $this->authorize('update', $rfq);

        $rfq->load('invitations.company');

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

            // #185 Обновление конкурсной документации (загруженные файлы заменяют/дополняют коллекции).
            app(\App\Services\ProcurementDocumentsService::class)->attachFromRequest($rfq, $request);

            DB::commit();

            return redirect()->route('rfqs.show', $rfq)
                ->with('success', 'Запрос цен обновлён');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Ошибка при обновлении: '.$e->getMessage());
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
            ->with('success', 'Запрос цен удалён');
    }

    /**
     * Подача заявки на участие
     */
    public function storeBid(Request $request, Rfq $rfq)
    {
        // #179 На этапе 1 коммерческого аукциона оценивается ТОЛЬКО цена
        $rules = [
            'company_id' => 'required|exists:companies,id',
            'price' => 'required|numeric|min:0',
            'comment' => 'nullable|string|max:1000',
        ];

        if (! $rfq->isCommercial()) {
            $rules['deadline'] = 'required|integer|min:1';
            $rules['advance_percent'] = 'required|numeric|min:0|max:100';
        }

        $request->validate($rules);

        // RFQ должен быть активным и не просроченным
        if (! $rfq->isActive() || $rfq->isExpired()) {
            return back()->withInput()->with('error', 'Приём заявок по этому запросу цен завершён.');
        }

        $company = Company::find($request->company_id);

        // #182: Заявку может подать только модератор компании — и только от верифицированной компании
        if (! $company || ! $company->isModerator(auth()->user())) {
            return back()->withInput()->with('error', 'Вы не можете подать заявку от имени этой компании.');
        }

        if (! $company->is_verified) {
            return back()->withInput()->with('error', 'Подавать заявки на участие могут только верифицированные компании. Пройдите верификацию компании.');
        }

        // Нельзя подавать заявку от компании-организатора
        if ($company->id === $rfq->company_id) {
            return back()->withInput()->with('error', 'Организатор не может подать заявку на собственный запрос цен.');
        }

        // Для закрытого RFQ компания должна быть приглашена
        if ($rfq->type !== 'open') {
            $isInvited = $rfq->invitations()->where('company_id', $company->id)->exists();
            if (! $isInvited) {
                return back()->withInput()->with('error', 'Ваша компания не приглашена к участию в этом запросе цен.');
            }
        }

        // Запрет повторной заявки от одной компании
        if ($rfq->bids()->where('company_id', $company->id)->exists()) {
            return back()->withInput()->with('error', 'Ваша компания уже подала заявку на этот запрос цен.');
        }

        DB::beginTransaction();

        try {
            $bid = RfqBid::create([
                'rfq_id' => $rfq->id,
                'company_id' => $request->company_id,
                'user_id' => auth()->id(),
                'price' => $request->price,
                'deadline' => $rfq->isCommercial() ? null : $request->deadline,
                'advance_percent' => $rfq->isCommercial() ? null : $request->advance_percent,
                'comment' => $request->comment,
                'status' => 'pending',
            ]);

            // #174: При подаче заявки обновляем статус приглашения на accepted
            $rfq->invitations()
                ->where('company_id', $request->company_id)
                ->where('status', 'pending')
                ->update(['status' => 'accepted']);

            DB::commit();

            return redirect()->route('rfqs.show', $rfq)
                ->with('success', 'Заявка подана успешно');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Ошибка при подаче заявки: '.$e->getMessage());
        }
    }

    /**
     * T8: Приглашение компании к участию в RFQ
     */
    public function storeInvitation(Request $request, Rfq $rfq)
    {
        // Проверка: пользователь может управлять RFQ
        if (! $rfq->canManage(auth()->user())) {
            return response()->json(['error' => 'Недостаточно прав'], 403);
        }

        // Проверка: RFQ не завершён
        if ($rfq->status === 'closed') {
            return response()->json(['error' => 'RFQ уже завершён'], 422);
        }

        $request->validate([
            'company_id' => 'required|exists:companies,id',
        ]);

        // Проверка: не приглашаем свою компанию
        if ($request->company_id == $rfq->company_id) {
            return response()->json(['error' => 'Нельзя пригласить компанию-организатора'], 422);
        }

        // Проверка: компания ещё не приглашена
        $exists = $rfq->invitations()->where('company_id', $request->company_id)->exists();
        if ($exists) {
            return response()->json(['error' => 'Компания уже приглашена'], 422);
        }

        $invitation = $rfq->invitations()->create([
            'company_id' => $request->company_id,
            'invited_by' => auth()->id(),
            'status' => 'pending',
        ]);

        // Отправка уведомления
        $company = Company::find($request->company_id);
        \App\Events\TenderInvitationSent::dispatch($rfq, $company, 'rfq');

        return response()->json([
            'success' => true,
            'invitation' => [
                'id' => $invitation->id,
                'company_name' => $company->name,
                'company_inn' => $company->inn,
                'status' => 'pending',
            ],
        ]);
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
     * Мои приглашения на участие в закрытых RFQ
     */
    public function myInvitations()
    {
        $userCompanyIds = auth()->user()->moderatedCompanies->pluck('id');

        $invitations = RfqInvitation::with(['rfq.company', 'company'])
            ->whereIn('company_id', $userCompanyIds)
            ->whereHas('rfq', function ($query) {
                $query->where('status', '!=', 'cancelled');
            })
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
        if (! $rfq->hasMedia('technical_specification')) {
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

            return back()->with('error', 'Ошибка при активации: '.$e->getMessage());
        }
    }

    /**
     * #148: отмена запроса цен организатором (до закрытия) с указанием причины.
     */
    public function cancel(Request $request, Rfq $rfq)
    {
        $this->authorize('cancel', $rfq);

        $validated = $request->validate([
            'cancellation_reason' => ['required', 'string', 'max:1000'],
        ], [
            'cancellation_reason.required' => 'Укажите причину отмены.',
        ]);

        $rfq->update([
            'status' => 'cancelled',
            'cancellation_reason' => $validated['cancellation_reason'],
        ]);

        return redirect()->route('rfqs.show', $rfq)
            ->with('success', 'Запрос цен отменён.');
    }
}
