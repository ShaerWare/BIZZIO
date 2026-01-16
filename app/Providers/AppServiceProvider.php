<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;

use App\Models\Rfq;
use App\Policies\RfqPolicy;
use App\Models\Auction;
use App\Policies\AuctionPolicy;
use App\Socialite\VKIDProvider;

// Events
use App\Events\ProjectInvitationSent;
use App\Events\TenderInvitationSent;
use App\Events\CommentCreated;
use App\Events\TenderClosed;
use App\Events\AuctionTradingStarted;

// Listeners
use App\Listeners\SendProjectInvitationNotification;
use App\Listeners\SendTenderInvitationNotification;
use App\Listeners\SendCommentNotification;
use App\Listeners\SendTenderClosedNotification;
use App\Listeners\SendAuctionTradingStartedNotification;

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
    }

    /**
     * Configure Socialite providers (Google, VK, VK ID).
     */
    protected function configureSocialite(): void
    {
        $socialite = $this->app->make(SocialiteFactory::class);

        // VK ID провайдер (новый API VK) - собственная реализация
        $socialite->extend('vkid', function ($app) use ($socialite) {
            $config = $app['config']['services.vkid'];
            return new VKIDProvider(
                $app['request'],
                $config['client_id'],
                $config['client_secret'],
                $config['redirect']
            );
        });

        // Старый VK-провайдер (для обратной совместимости)
        $socialite->extend('vk', function ($app) use ($socialite) {
            $config = $app['config']['services.vk'];
            return $socialite->buildProvider(\SocialiteProviders\VKontakte\Provider::class, $config);
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
    }
}