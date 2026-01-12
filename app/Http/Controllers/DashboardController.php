<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class DashboardController extends Controller
{
    /**
     * Display the dashboard with activity feed.
     */
    public function index()
    {
        $activities = Activity::with(['causer', 'subject'])
            ->latest()
            ->take(20)
            ->get();

        $unreadNotificationsCount = auth()->user()->unreadNotifications()->count();

        return view('dashboard', compact('activities', 'unreadNotificationsCount'));
    }

    /**
     * Load more activities (AJAX).
     */
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
