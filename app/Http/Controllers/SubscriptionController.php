<?php

namespace App\Http\Controllers;

use App\Events\UserSubscribed;
use App\Models\Company;
use App\Models\Subscription;
use App\Models\User;

class SubscriptionController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $userSubscriptions = $user->subscriptions()
            ->where('subscribable_type', User::class)
            ->with('subscribable')
            ->latest()
            ->get();

        $companySubscriptions = $user->subscriptions()
            ->where('subscribable_type', Company::class)
            ->with('subscribable')
            ->latest()
            ->get();

        return view('subscriptions.index', compact('userSubscriptions', 'companySubscriptions'));
    }

    public function subscribeUser(User $user)
    {
        $subscriber = auth()->user();

        if ($subscriber->id === $user->id) {
            return back()->with('error', 'Нельзя подписаться на себя.');
        }

        $subscription = Subscription::firstOrCreate([
            'subscriber_id' => $subscriber->id,
            'subscribable_type' => User::class,
            'subscribable_id' => $user->id,
        ]);

        if ($subscription->wasRecentlyCreated) {
            UserSubscribed::dispatch($subscription);
        }

        return back()->with('success', "Вы подписались на {$user->name}.");
    }

    public function unsubscribeUser(User $user)
    {
        auth()->user()->subscriptions()
            ->where('subscribable_type', User::class)
            ->where('subscribable_id', $user->id)
            ->delete();

        return back()->with('success', "Вы отписались от {$user->name}.");
    }

    public function subscribeCompany(Company $company)
    {
        $subscriber = auth()->user();

        $subscription = Subscription::firstOrCreate([
            'subscriber_id' => $subscriber->id,
            'subscribable_type' => Company::class,
            'subscribable_id' => $company->id,
        ]);

        if ($subscription->wasRecentlyCreated) {
            UserSubscribed::dispatch($subscription);
        }

        return back()->with('success', "Вы подписались на {$company->name}.");
    }

    public function unsubscribeCompany(Company $company)
    {
        auth()->user()->subscriptions()
            ->where('subscribable_type', Company::class)
            ->where('subscribable_id', $company->id)
            ->delete();

        return back()->with('success', "Вы отписались от {$company->name}.");
    }
}
