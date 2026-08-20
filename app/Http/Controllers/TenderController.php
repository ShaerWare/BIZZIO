<?php

namespace App\Http\Controllers;

use App\Models\Auction;
use App\Models\AuctionBid;
use App\Models\AuctionInvitation;
use App\Models\Company;
use App\Models\Rfq;
use App\Models\RfqBid;
use App\Models\RfqInvitation;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class TenderController extends Controller
{
    /**
     * Единый каталог тендеров (RFQ + Аукционы)
     */
    public function index(Request $request)
    {
        $kind = $request->get('kind', '');
        $items = collect();

        // RFQ
        if ($kind !== 'auction') {
            $rfqQuery = Rfq::with(['company', 'creator.badges', 'bids', 'linkedAuction'])
                ->orderBy('created_at', 'desc');

            $this->applyDraftFilter($rfqQuery);
            $this->applyFilters($rfqQuery, $request);
            $this->hideLaunchedCommercialRfqs($rfqQuery);

            $rfqs = $rfqQuery->get()->map(fn ($r) => [
                'model' => $r,
                'kind' => 'rfq',
                'created_at' => $r->created_at,
            ]);

            $items = $items->concat($rfqs);
        }

        // Аукционы
        if ($kind !== 'rfq') {
            $auctionQuery = Auction::with(['company.industry', 'creator.badges', 'bids'])
                ->orderBy('created_at', 'desc');

            $this->applyDraftFilter($auctionQuery);
            $this->applyClosedAuctionFilter($auctionQuery);
            $this->applyFilters($auctionQuery, $request);

            $auctions = $auctionQuery->get()->map(fn ($a) => [
                'model' => $a,
                'kind' => 'auction',
                'created_at' => $a->created_at,
            ]);

            $items = $items->concat($auctions);
        }

        $paginator = $this->paginate($items->sortByDesc('created_at'), $request, 'tenders.index');

        return view('tenders.index', ['items' => $paginator]);
    }

    /**
     * Мои тендеры (организатор)
     */
    public function myTenders()
    {
        $userCompanies = auth()->user()->moderatedCompanies()->pluck('companies.id');

        $rfqsQuery = Rfq::with(['company', 'bids', 'winnerBid.company', 'linkedAuction'])
            ->whereIn('company_id', $userCompanies)
            ->orderBy('created_at', 'desc');
        $this->hideLaunchedCommercialRfqs($rfqsQuery);

        $rfqs = $rfqsQuery->get()
            ->map(fn ($r) => [
                'model' => $r,
                'kind' => 'rfq',
                'created_at' => $r->created_at,
            ]);

        $auctions = Auction::with(['company', 'bids', 'winnerBid.company'])
            ->where(function ($query) use ($userCompanies) {
                $query->whereIn('company_id', $userCompanies)
                    ->orWhere('created_by', auth()->id());
            })
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($a) => [
                'model' => $a,
                'kind' => 'auction',
                'created_at' => $a->created_at,
            ]);

        $paginator = $this->paginate($rfqs->concat($auctions)->sortByDesc('created_at'), request(), 'tenders.my');

        return view('tenders.my-tenders', ['items' => $paginator]);
    }

    /**
     * Мои заявки (участник)
     */
    public function myBids()
    {
        $userCompanies = auth()->user()->moderatedCompanies()->pluck('companies.id');

        // #231: заявки, чей тендер мягко удалён, остаются сиротами ($bid->rfq/auction === null)
        // и роняют вью на route('rfqs.show', null) → 500. whereHas исключает их (уважает SoftDeletes).
        $rfqBids = RfqBid::with(['rfq.company', 'company'])
            ->whereIn('company_id', $userCompanies)
            ->whereHas('rfq')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($b) => [
                'model' => $b,
                'kind' => 'rfq',
                'created_at' => $b->created_at,
            ]);

        $auctionBids = AuctionBid::with(['auction.company', 'company'])
            ->whereIn('company_id', $userCompanies)
            ->whereHas('auction')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($b) => [
                'model' => $b,
                'kind' => 'auction',
                'created_at' => $b->created_at,
            ]);

        $paginator = $this->paginate($rfqBids->concat($auctionBids)->sortByDesc('created_at'), request(), 'tenders.bids.my');

        return view('tenders.my-bids', ['items' => $paginator]);
    }

    /**
     * Мои приглашения
     */
    public function myInvitations()
    {
        $userCompanies = auth()->user()->moderatedCompanies()->pluck('companies.id');

        $rfqInvitations = RfqInvitation::with(['rfq.company', 'company'])
            ->whereIn('company_id', $userCompanies)
            ->whereHas('rfq', fn ($q) => $q->where('status', '!=', 'cancelled'))
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($i) => [
                'model' => $i,
                'kind' => 'rfq',
                'created_at' => $i->created_at,
            ]);

        $auctionInvitations = AuctionInvitation::with(['auction.company', 'company'])
            ->whereIn('company_id', $userCompanies)
            ->whereHas('auction') // #231: исключаем приглашения на мягко удалённые аукционы
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($i) => [
                'model' => $i,
                'kind' => 'auction',
                'created_at' => $i->created_at,
            ]);

        $paginator = $this->paginate($rfqInvitations->concat($auctionInvitations)->sortByDesc('created_at'), request(), 'tenders.invitations.my');

        return view('tenders.my-invitations', ['items' => $paginator]);
    }

    /**
     * Правила проведения тендеров (T9)
     */
    public function rules()
    {
        return view('tenders.rules');
    }

    /**
     * #284 Подсказки для фильтра «Компания-организатор».
     *
     * Возвращаем только те компании, у которых есть закупки, — иначе в подсказках окажутся
     * организаторы, по которым список всегда пуст.
     */
    public function organizers(Request $request)
    {
        $search = trim((string) $request->get('q', ''));

        if (mb_strlen($search) < 2) {
            return response()->json([]);
        }

        $op = \DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $companies = Company::query()
            ->where('name', $op, "%{$search}%")
            ->where(fn ($q) => $q->whereHas('rfqs')->orWhereHas('auctions'))
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name']);

        return response()->json($companies);
    }

    /**
     * Скрываем черновики от посторонних (C3)
     */
    private function applyDraftFilter($query): void
    {
        if (auth()->check()) {
            $userCompanies = auth()->user()->moderatedCompanies()->pluck('companies.id');
            $query->where(function ($q) use ($userCompanies) {
                $q->where('status', '!=', 'draft')
                    ->orWhereIn('company_id', $userCompanies);
            });
        } else {
            $query->where('status', '!=', 'draft');
        }
    }

    /**
     * #233: Коммерческая процедура присутствует в каталоге как пара RFQ (этап 1) +
     * Auction (этап 2). После запуска этапа 2 показываем только аукцион, а этап-1 RFQ
     * (у него выставлен linked_auction_id) скрываем — иначе одна процедура дублируется.
     * Незапущенные коммерческие RFQ (идёт приём заявок этапа 1) остаются видимыми.
     */
    private function hideLaunchedCommercialRfqs($query): void
    {
        $query->where(function ($q) {
            $q->where('procedure', '!=', 'commercial')
                ->orWhereNull('linked_auction_id');
        });
    }

    /**
     * #38: Закрытые аукционы видны только организатору и приглашённым
     */
    private function applyClosedAuctionFilter($query): void
    {
        if (auth()->check()) {
            $userCompanies = auth()->user()->moderatedCompanies()->pluck('companies.id');
            $query->where(function ($q) use ($userCompanies) {
                $q->where('type', '!=', 'closed')
                    ->orWhereIn('company_id', $userCompanies)
                    ->orWhereHas('invitations', function ($inv) use ($userCompanies) {
                        $inv->whereIn('company_id', $userCompanies);
                    });
            });
        } else {
            $query->where('type', '!=', 'closed');
        }
    }

    /**
     * Применяем фильтры поиска, статуса и типа
     */
    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('status') && $request->status !== 'draft') {
            $query->where('status', $request->status);
        } elseif ($request->filled('status') && $request->status === 'draft' && auth()->check()) {
            $userCompanies = auth()->user()->moderatedCompanies()->pluck('companies.id');
            $query->where('status', 'draft')->whereIn('company_id', $userCompanies);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // #284 Фильтр по компании-организатору: выбор из подсказок фиксирует organizer_id,
        // но и просто введённый текст должен что-то находить.
        if ($request->filled('organizer_id')) {
            $query->where('company_id', (int) $request->organizer_id);
        } elseif ($request->filled('organizer')) {
            $op = \DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $organizer = $request->organizer;
            $query->whereHas('company', fn ($q) => $q->where('name', $op, "%{$organizer}%"));
        }

        // #179 Фильтр по виду процедуры (standard / commercial)
        if ($request->filled('procedure')) {
            $query->where('procedure', $request->procedure);
        }
    }

    /**
     * Ручная пагинация объединённой коллекции
     */
    private function paginate($collection, Request $request, string $routeName, int $perPage = 20): LengthAwarePaginator
    {
        $page = (int) $request->get('page', 1);
        $total = $collection->count();
        $items = $collection->forPage($page, $perPage)->values();

        return new LengthAwarePaginator($items, $total, $perPage, $page, [
            'path' => route($routeName),
            'query' => $request->query(),
        ]);
    }
}
