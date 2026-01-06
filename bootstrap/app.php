<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withSchedule(function (Schedule $schedule) {
        // Парсинг RSS каждые 15 минут
        $schedule->command('rss:parse')
            ->everyFifteenMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        // Очистка старых новостей (ежедневно в 02:00 UTC+3)
        $schedule->command('news:clean-old')
            ->dailyAt('02:00')
            ->withoutOverlapping();

            // Обновление статусов аукционов (каждую минуту)
        $schedule->job(new \App\Jobs\UpdateAuctionStatuses())
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();
    
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();