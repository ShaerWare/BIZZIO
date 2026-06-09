<?php

namespace App\Providers;

use App\Models\Auction;
use App\Models\Company;
use App\Models\Rfq;
use App\Policies\AuctionPolicy;
use App\Policies\RfqPolicy;
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
