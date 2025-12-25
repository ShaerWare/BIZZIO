<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ===================================
// CRON-ЗАДАЧИ ДЛЯ АУКЦИОНОВ
// ===================================

// Проверка истёкших аукционов (каждую минуту)
Schedule::command('auctions:check-expired')->everyMinute();