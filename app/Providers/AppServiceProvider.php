<?php

namespace App\Providers;

use App\Events\AuctionTradingStarted;
use App\Events\CommentCreated;
use App\Events\CompanyCreated;
use App\Events\ProjectInvitationSent;
use App\Events\ProjectJoinRequestCreated;
use App\Events\ProjectJoinRequestReviewed;
use App\Events\ProjectUserInvited;
use App\Events\TenderClosed;
use App\Events\TenderInvitationSent;
use App\Listeners\SendAuctionTradingStartedNotification;
use App\Listeners\SendCommentNotification;
use App\Listeners\SendCompanyCreatedNotification;
use App\Listeners\SendProjectInvitationNotification;
use App\Listeners\SendProjectJoinRequestNotification;
use App\Listeners\SendProjectJoinRequestReviewedNotification;
use App\Listeners\SendProjectUserInvitedNotification;
use App\Listeners\SendTenderClosedNotification;
use App\Listeners\SendTenderInvitationNotification;
// Events
use App\Models\Auction;
use App\Models\Rfq;
use App\Policies\AuctionPolicy;
use App\Policies\RfqPolicy;
// Listeners
use App\Models\Company;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureSocialite();
        $this->configureHttps();
        $this->registerPolicies();
        $this->registerEventListeners();
        $this->registerRouteBindings();
    }

    /**
     * Configure Socialite providers (Google, Yandex).
     */
    protected function configureSocialite(): void
    {
        $socialite = $this->app->make(\Laravel\Socialite\Contracts\Factory::class);
        $socialite->extend('yandex', function () use ($socialite) {
            return $socialite->buildProvider(
                \App\Socialite\YandexProvider::class,
                config('services.yandex')
            );
        });
    }

    /**
     * Configure HTTPS based on environment.
     * Forces HTTPS on production or when APP_URL uses https.
     */
    protected function configureHttps(): void
    {
        $appUrl = config('app.url', '');

        // Force HTTPS if:
        // 1. APP_ENV is production, OR
        // 2. APP_URL starts with https://
        if ($this->app->environment('production') || str_starts_with($appUrl, 'https://')) {
            URL::forceScheme('https');
        }
    }

    /**
     * Register authorization policies.
     */
    protected function registerPolicies(): void
    {
        Gate::policy(Rfq::class, RfqPolicy::class);
        Gate::policy(Auction::class, AuctionPolicy::class);
    }

    /**
     * Register event listeners.
     */
    protected function registerEventListeners(): void
    {
        Event::listen(ProjectInvitationSent::class, SendProjectInvitationNotification::class);
        Event::listen(TenderInvitationSent::class, SendTenderInvitationNotification::class);
        Event::listen(CommentCreated::class, SendCommentNotification::class);
        Event::listen(TenderClosed::class, SendTenderClosedNotification::class);
        Event::listen(AuctionTradingStarted::class, SendAuctionTradingStartedNotification::class);
        Event::listen(CompanyCreated::class, SendCompanyCreatedNotification::class);
        Event::listen(ProjectUserInvited::class, SendProjectUserInvitedNotification::class);
        Event::listen(ProjectJoinRequestCreated::class, SendProjectJoinRequestNotification::class);
        Event::listen(ProjectJoinRequestReviewed::class, SendProjectJoinRequestReviewedNotification::class);
    }

    /**
     * Register custom route model bindings.
     * Company uses slug for public routes but id for admin (Orchid) routes.
     */
    protected function registerRouteBindings(): void
    {
        Route::bind('company', function ($value) {
            if (is_numeric($value)) {
                return Company::findOrFail($value);
            }

            return Company::where('slug', $value)->firstOrFail();
        });
    }
}
