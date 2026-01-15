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
        // Регистрация VK ID провайдера для Socialite (новый API VK)
        Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
            $event->extendSocialite('vkid', \SocialiteProviders\VKID\Provider::class);
        });

        // Регистрация старого VK-провайдера (для обратной совместимости)
        $socialite = $this->app->make(SocialiteFactory::class);
        $socialite->extend('vk', function ($app) use ($socialite) {
            $config = $app['config']['services.vk'];
            return $socialite->buildProvider(\SocialiteProviders\VKontakte\Provider::class, $config);
        });
        // HTTPS только для продакшена или если APP_URL начинается с https
        if ($this->app->environment('production') || str_starts_with(config('app.url'), 'https')) {
            URL::forceScheme('https');
        }
        // Регистрация Policy для RFQ
        Gate::policy(Rfq::class, RfqPolicy::class);

        // Регистрация Policy для Auction
        Gate::policy(Auction::class, AuctionPolicy::class);

                // ========== EVENT LISTENERS ==========
        Event::listen(
            ProjectInvitationSent::class,
            SendProjectInvitationNotification::class
        );

        Event::listen(
            TenderInvitationSent::class,
            SendTenderInvitationNotification::class
        );

        Event::listen(
            CommentCreated::class,
            SendCommentNotification::class
        );

        Event::listen(
            TenderClosed::class,
            SendTenderClosedNotification::class
        );

        Event::listen(
            AuctionTradingStarted::class,
            SendAuctionTradingStartedNotification::class
        );
    }
}