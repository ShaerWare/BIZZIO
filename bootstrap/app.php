<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Session\TokenMismatchException;

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
        // N1: Парсинг RSS каждые 5 минут (источники сами контролируют свой интервал)
        $schedule->command('rss:parse')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        // Очистка старых новостей (ежедневно в 02:00 UTC+3)
        $schedule->command('news:clean-old')
            ->dailyAt('02:00')
            ->withoutOverlapping();

            // Обновление статусов аукционов (каждую минуту)
        $schedule->command('auctions:update-statuses')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();
    
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // 419 CSRF token expired → redirect to login instead of error page
        $exceptions->renderable(function (TokenMismatchException $e, $request) {
            return redirect()->route('login')
                ->with('status', 'Сессия истекла. Пожалуйста, войдите снова.');
        });
    })->create();