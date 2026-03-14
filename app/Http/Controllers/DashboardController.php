<?php

namespace App\Http\Controllers;

use App\Models\Auction;
use App\Models\AuctionBid;
use App\Models\AuctionInvitation;
use App\Models\Company;
use App\Models\News;
use App\Models\Post;
use App\Models\Project;
use App\Models\Rfq;
use App\Models\RfqBid;
use App\Models\RfqInvitation;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Компании пользователя (один запрос для всех виджетов)
        $userCompanies = $user->moderatedCompanies()->get();
        $companyIds = $userCompanies->pluck('id');

        // === Левая колонка ===
        $pendingJoinRequests = $user->pendingCompanyRequests()
            ->with('company')
            ->latest()
            ->take(1)
            ->get();

        $userProjects = Project::where(function ($q) use ($companyIds) {
            $q->whereIn('company_id', $companyIds)
                ->orWhereHas('participants', fn ($q2) => $q2->whereIn('companies.id', $companyIds));
        })
            ->latest()
            ->take(10)
            ->get();

        // === Контекст подписок ===
        $subscriptionContext = $this->getSubscriptionContext($user);
        $feedUserIds = $subscriptionContext['feedUserIds'];
        $directCompanyIds = $subscriptionContext['directCompanyIds'];

        // === Центральная колонка ===
        $keywords = $user->keywords()->pluck('keyword')->toArray();
        $latestNews = News::query()
            ->when(! empty($keywords), fn ($q) => $q->searchByKeywords($keywords, 'any'))
            ->latest('published_at')
            ->take(3)
            ->get();

        // Посты: от подписок + друзей друзей (если есть подписки)
        if ($feedUserIds->isNotEmpty()) {
            $recentPosts = Post::with(['user', 'media'])
                ->where(function ($q) use ($feedUserIds, $user) {
                    $q->whereIn('user_id', $feedUserIds)
                        ->orWhere('user_id', $user->id);
                })
                ->latest()
                ->take(10)
                ->get();
        } else {
            $recentPosts = Post::with(['user', 'media'])
                ->where('user_id', $user->id)
                ->latest()
                ->take(10)
                ->get();
        }

        // Активности: от прямых подписок (1-й уровень)
        $directUserIds = $subscriptionContext['directUserIds'];
        if ($directUserIds->isNotEmpty() || $directCompanyIds->isNotEmpty()) {
            $activities = Activity::with(['causer', 'subject'])
                ->where(function ($q) use ($directUserIds, $directCompanyIds, $user) {
                    $q->whereIn('causer_id', $directUserIds->push($user->id))
                        ->orWhere(function ($q2) use ($directCompanyIds) {
                            $q2->where('subject_type', Company::class)
                                ->whereIn('subject_id', $directCompanyIds);
                        });
                })
                ->latest()
                ->take(20)
                ->get();
        } else {
            $activities = Activity::with(['causer', 'subject'])
                ->where('causer_id', $user->id)
                ->latest()
                ->take(20)
                ->get();
        }

        // Рекомендации компаний при пустой ленте
        $recommendedCompanies = collect();
        if ($feedUserIds->isEmpty()) {
            $recommendedCompanies = Company::verified()
                ->whereNotIn('id', $companyIds)
                ->inRandomOrder()
                ->take(5)
                ->get();
        }

        // === Правая колонка ===
        $myRfqs = Rfq::whereIn('company_id', $companyIds)
            ->latest()
            ->take(3)
            ->get();

        $myAuctions = Auction::whereIn('company_id', $companyIds)
            ->latest()
            ->take(3)
            ->get();

        $statusInfo = function (string $status, $startDate, $endDate, string $type) {
            if ($status === 'active') {
                if ($startDate && $startDate->isFuture()) {
                    return ['Скоро', 'bg-yellow-100 text-yellow-800'];
                } elseif ($endDate && $endDate->isPast()) {
                    return [$type === 'rfq' ? 'Подведение итогов' : 'Завершён приём', 'bg-orange-100 text-orange-800'];
                }

                return ['Приём заявок', 'bg-green-100 text-green-800'];
            }

            return match ($status) {
                'trading' => ['Торги', 'bg-emerald-100 text-emerald-800'],
                'closed' => ['Завершён', 'bg-gray-100 text-gray-800'],
                'cancelled' => ['Отменён', 'bg-red-100 text-red-800'],
                'draft' => ['Черновик', 'bg-yellow-100 text-yellow-800'],
                default => [$status, 'bg-gray-100 text-gray-800'],
            };
        };

        $myTenders = collect($myRfqs->map(function ($r) use ($statusInfo) {
            [$label, $color] = $statusInfo($r->status, $r->start_date, $r->end_date, 'rfq');

            return [
                'type' => 'rfq',
                'title' => $r->title,
                'number' => $r->number,
                'status_label' => $label,
                'status_color' => $color,
                'url' => route('rfqs.show', $r),
            ];
        }))->merge($myAuctions->map(function ($a) use ($statusInfo) {
            [$label, $color] = $statusInfo($a->status, $a->start_date, $a->end_date, 'auction');

            return [
                'type' => 'auction',
                'title' => $a->title,
                'number' => $a->number,
                'status_label' => $label,
                'status_color' => $color,
                'url' => route('auctions.show', $a),
            ];
        }))->sortByDesc('number')->take(3)->values();

        $rfqInvitations = RfqInvitation::whereIn('company_id', $companyIds)
            ->with('rfq')
            ->latest()
            ->take(3)
            ->get();

        $auctionInvitations = AuctionInvitation::whereIn('company_id', $companyIds)
            ->with('auction')
            ->latest()
            ->take(3)
            ->get();

        $invitationStatusLabels = [
            'pending' => ['Ожидает', 'bg-yellow-100 text-yellow-800'],
            'accepted' => ['Принято', 'bg-green-100 text-green-800'],
            'declined' => ['Отклонено', 'bg-red-100 text-red-800'],
        ];

        $myInvitations = collect($rfqInvitations->map(function ($i) use ($statusInfo, $invitationStatusLabels) {
            [$tenderLabel, $tenderColor] = $statusInfo($i->rfq->status ?? 'draft', $i->rfq->start_date ?? null, $i->rfq->end_date ?? null, 'rfq');
            [$invLabel, $invColor] = $invitationStatusLabels[$i->status] ?? ['—', 'bg-gray-100 text-gray-800'];

            return [
                'type' => 'rfq',
                'title' => $i->rfq->title ?? '',
                'number' => $i->rfq->number ?? '',
                'inv_label' => $invLabel,
                'inv_color' => $invColor,
                'tender_label' => $tenderLabel,
                'tender_color' => $tenderColor,
                'url' => route('rfqs.show', $i->rfq_id),
            ];
        }))->merge($auctionInvitations->map(function ($i) use ($statusInfo, $invitationStatusLabels) {
            [$tenderLabel, $tenderColor] = $statusInfo($i->auction->status ?? 'draft', $i->auction->start_date ?? null, $i->auction->end_date ?? null, 'auction');
            [$invLabel, $invColor] = $invitationStatusLabels[$i->status] ?? ['—', 'bg-gray-100 text-gray-800'];

            return [
                'type' => 'auction',
                'title' => $i->auction->title ?? '',
                'number' => $i->auction->number ?? '',
                'inv_label' => $invLabel,
                'inv_color' => $invColor,
                'tender_label' => $tenderLabel,
                'tender_color' => $tenderColor,
                'url' => route('auctions.show', $i->auction_id),
            ];
        }))->take(3)->values();

        $rfqBids = RfqBid::whereIn('company_id', $companyIds)
            ->with('rfq')
            ->latest()
            ->take(3)
            ->get();

        $auctionBids = AuctionBid::whereIn('company_id', $companyIds)
            ->with('auction')
            ->latest()
            ->take(3)
            ->get();

        $myBids = collect($rfqBids->map(fn ($b) => [
            'type' => 'rfq',
            'title' => $b->rfq->title ?? '',
            'number' => $b->rfq->number ?? '',
            'price' => $b->price,
            'currency_symbol' => $b->rfq->currency_symbol ?? '₽',
            'url' => route('rfqs.show', $b->rfq_id),
        ]))->merge($auctionBids->map(fn ($b) => [
            'type' => 'auction',
            'title' => $b->auction->title ?? '',
            'number' => $b->auction->number ?? '',
            'price' => $b->price,
            'currency_symbol' => $b->auction->currency_symbol ?? '₽',
            'url' => route('auctions.show', $b->auction_id),
        ]))->take(3)->values();

        return view('dashboard', compact(
            'userCompanies',
            'pendingJoinRequests',
            'userProjects',
            'latestNews',
            'recentPosts',
            'activities',
            'myTenders',
            'myInvitations',
            'myBids',
            'recommendedCompanies',
        ));
    }

    public function loadMoreActivities(Request $request)
    {
        $offset = $request->input('offset', 0);
        $user = auth()->user();
        $subscriptionContext = $this->getSubscriptionContext($user);
        $directUserIds = $subscriptionContext['directUserIds'];
        $directCompanyIds = $subscriptionContext['directCompanyIds'];

        if ($directUserIds->isNotEmpty() || $directCompanyIds->isNotEmpty()) {
            $activities = Activity::with(['causer', 'subject'])
                ->where(function ($q) use ($directUserIds, $directCompanyIds, $user) {
                    $q->whereIn('causer_id', $directUserIds->push($user->id))
                        ->orWhere(function ($q2) use ($directCompanyIds) {
                            $q2->where('subject_type', Company::class)
                                ->whereIn('subject_id', $directCompanyIds);
                        });
                })
                ->latest()
                ->skip($offset)
                ->take(10)
                ->get();
        } else {
            $activities = Activity::with(['causer', 'subject'])
                ->where('causer_id', $user->id)
                ->latest()
                ->skip($offset)
                ->take(10)
                ->get();
        }

        return view('partials.activity-items', compact('activities'));
    }

    private function getSubscriptionContext(User $user): array
    {
        // ID пользователей, на которых подписан напрямую
        $directUserIds = $user->subscriptions()
            ->where('subscribable_type', User::class)
            ->pluck('subscribable_id');

        // ID компаний, на которые подписан напрямую
        $directCompanyIds = $user->subscriptions()
            ->where('subscribable_type', Company::class)
            ->pluck('subscribable_id');

        // Друзья друзей: на кого подписаны мои подписки
        $fofUserIds = collect();
        if ($directUserIds->isNotEmpty()) {
            $fofUserIds = Subscription::whereIn('subscriber_id', $directUserIds)
                ->where('subscribable_type', User::class)
                ->pluck('subscribable_id');
        }

        // Объединяем: прямые + друзья друзей, без текущего пользователя
        $feedUserIds = $directUserIds->merge($fofUserIds)
            ->unique()
            ->reject(fn ($id) => $id === $user->id);

        return [
            'directUserIds' => $directUserIds,
            'directCompanyIds' => $directCompanyIds,
            'feedUserIds' => $feedUserIds,
        ];
    }
}
