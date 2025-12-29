<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use Illuminate\Support\Facades\Gate;
use App\Models\Rfq;
use App\Policies\RfqPolicy;
use Illuminate\Support\Facades\URL;
use App\Models\Auction;
use App\Policies\AuctionPolicy;

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
        // Регистрация VK-провайдера для Socialite
        $socialite = $this->app->make(SocialiteFactory::class);
        $socialite->extend('vk', function ($app) use ($socialite) {
            $config = $app['config']['services.vk'];
            return $socialite->buildProvider(\SocialiteProviders\VKontakte\Provider::class, $config);
        });
        /*
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
            */
        URL::forceScheme('https');
        // Регистрация Policy для RFQ
        Gate::policy(Rfq::class, RfqPolicy::class);

        // Регистрация Policy для Auction
        Gate::policy(Auction::class, AuctionPolicy::class);
    }
}