<?php

namespace App\Http\Controllers;

use App\Models\Auction;
use App\Models\AuctionBid;
use App\Models\AuctionInvitation;
use App\Models\News;
use App\Models\Post;
use App\Models\Project;
use App\Models\Rfq;
use App\Models\RfqBid;
use App\Models\RfqInvitation;
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

        // === Центральная колонка ===
        $keywords = $user->keywords()->pluck('keyword')->toArray();
        $latestNews = News::query()
            ->when(! empty($keywords), fn ($q) => $q->searchByKeywords($keywords, 'any'))
            ->latest('published_at')
            ->take(3)
            ->get();

        $recentPosts = Post::with(['user', 'media'])
            ->latest()
            ->take(10)
            ->get();

        $activities = Activity::with(['causer', 'subject'])
            ->latest()
            ->take(20)
            ->get();

        // === Правая колонка ===
        $myRfqs = Rfq::whereIn('company_id', $companyIds)
            ->latest()
            ->take(3)
            ->get();

        $myAuctions = Auction::whereIn('company_id', $companyIds)
            ->latest()
            ->take(3)
            ->get();

        $myTenders = collect($myRfqs->map(fn ($r) => [
            'type' => 'rfq',
            'title' => $r->title,
            'number' => $r->number,
            'status' => $r->status,
            'url' => route('rfqs.show', $r),
        ]))->merge($myAuctions->map(fn ($a) => [
            'type' => 'auction',
            'title' => $a->title,
            'number' => $a->number,
            'status' => $a->status,
            'url' => route('auctions.show', $a),
        ]))->sortByDesc('number')->take(3)->values();

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

        $myInvitations = collect($rfqInvitations->map(fn ($i) => [
            'type' => 'rfq',
            'title' => $i->rfq->title ?? '',
            'number' => $i->rfq->number ?? '',
            'status' => $i->status,
            'url' => route('rfqs.show', $i->rfq_id),
        ]))->merge($auctionInvitations->map(fn ($i) => [
            'type' => 'auction',
            'title' => $i->auction->title ?? '',
            'number' => $i->auction->number ?? '',
            'status' => $i->status,
            'url' => route('auctions.show', $i->auction_id),
        ]))->take(3)->values();

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
            'url' => route('rfqs.show', $b->rfq_id),
        ]))->merge($auctionBids->map(fn ($b) => [
            'type' => 'auction',
            'title' => $b->auction->title ?? '',
            'number' => $b->auction->number ?? '',
            'price' => $b->price,
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
        ));
    }

    public function loadMoreActivities(Request $request)
    {
        $offset = $request->input('offset', 0);

        $activities = Activity::with(['causer', 'subject'])
            ->latest()
            ->skip($offset)
            ->take(10)
            ->get();

        return view('partials.activity-items', compact('activities'));
    }
}
