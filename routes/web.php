<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\SocialiteController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RfqController;
use App\Http\Controllers\CompanyJoinRequestController;
use App\Http\Controllers\CompanyModeratorController;
use App\Http\Controllers\AuctionController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\UserKeywordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// ========================================================================
// COMPANIES ROUTES
// ========================================================================

// Publicly accessible routes for viewing companies

Route::post('/auth/vk/callback', [SocialiteController::class, 'vkIdCallback'])->name('vkid.callback');
// Authenticated routes for managing companies (create, store, edit, update, delete)
// CRUD компаний (создание, обновление, удаление только для авторизованных)
Route::middleware('auth')->group(function () {
    // Using resource routes for authenticated actions (create, store, edit, update, destroy)
    // 'index' and 'show' are excluded here as they are defined above as public.
    Route::get('companies/create', [CompanyController::class, 'create'])->name('companies.create');
    Route::post('companies', [CompanyController::class, 'store'])->name('companies.store');
    Route::get('companies/{company}/edit', [CompanyController::class, 'edit'])->name('companies.edit');
    Route::put('companies/{company}', [CompanyController::class, 'update'])->name('companies.update');
    Route::delete('companies/{company}', [CompanyController::class, 'destroy'])->name('companies.destroy');

    // Запросы на присоединение к компании 
    Route::get('/my-join-requests', [CompanyJoinRequestController::class, 'index'])
        ->name('join-requests.index');
    Route::post('/companies/{company}/join-requests', [CompanyJoinRequestController::class, 'store'])
        ->name('companies.join-requests.store');
    Route::delete('/join-requests/{request}', [CompanyJoinRequestController::class, 'destroy'])
        ->name('join-requests.destroy');
    Route::post('/join-requests/{request}/approve', [CompanyJoinRequestController::class, 'approve'])
        ->name('join-requests.approve');
    Route::post('/join-requests/{request}/reject', [CompanyJoinRequestController::class, 'reject'])
        ->name('join-requests.reject');
    
    // Управление модераторами компании
    Route::post('/companies/{company}/moderators', [CompanyModeratorController::class, 'store'])
        ->name('companies.moderators.store');
    Route::put('/companies/{company}/moderators/{user}', [CompanyModeratorController::class, 'update'])
        ->name('companies.moderators.update');
    Route::delete('/companies/{company}/moderators/{user}', [CompanyModeratorController::class, 'destroy'])
        ->name('companies.moderators.destroy');
});
// Публичный доступ к списку и просмотру компаний
Route::get('companies', [CompanyController::class, 'index'])->name('companies.index');
Route::get('companies/{company}', [CompanyController::class, 'show'])->name('companies.show');

// ========================================================================
// DASHBOARD & PROFILE ROUTES
// ========================================================================

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/activities', [DashboardController::class, 'loadMoreActivities'])->name('dashboard.activities');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ========================================================================
// NOTIFICATIONS ROUTES
// ========================================================================

Route::middleware('auth')->prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    Route::post('/{notification}/read', [NotificationController::class, 'markAsRead'])->name('read');
    Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('read-all');
    Route::get('/unread-count', [NotificationController::class, 'unreadCount'])->name('unread-count');
});

// ========================================================================
// SOCIALITE AUTH ROUTES
// ========================================================================

Route::get('/auth/{provider}/redirect', [SocialiteController::class, 'redirect'])
    ->name('socialite.redirect');
Route::get('/auth/{provider}/callback', [SocialiteController::class, 'callback'])
    ->name('socialite.callback');

// ========================================================================
// PROJECTS ROUTES
// ========================================================================

// Проекты (публичные: index, show; приватные: create, store, edit, update, destroy)
Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
Route::get('/projects/{project:slug}', [ProjectController::class, 'show'])->name('projects.show');

Route::middleware('auth')->group(function () {
    Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project:slug}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
    Route::put('/projects/{project:slug}', [ProjectController::class, 'update'])->name('projects.update');
    Route::delete('/projects/{project:slug}', [ProjectController::class, 'destroy'])->name('projects.destroy');

    // Комментарии к проектам
    Route::post('/projects/{project:slug}/comments', [ProjectController::class, 'storeComment'])->name('projects.comments.store');
    Route::put('/comments/{comment}', [ProjectController::class, 'updateComment'])->name('comments.update');
    Route::delete('/comments/{comment}', [ProjectController::class, 'destroyComment'])->name('comments.destroy');
});

// ========================================================================
// RFQ ROUTES
// ========================================================================

// Публичные роуты
Route::get('/rfqs', [RfqController::class, 'index'])->name('rfqs.index');
Route::get('/rfqs/{rfq}', [RfqController::class, 'show'])->name('rfqs.show');

Route::middleware(['auth'])->group(function () {
    // Мои RFQ (организатор)
    Route::get('/my-rfqs', [RfqController::class, 'myRfqs'])->name('rfqs.my');
    
    // Мои заявки (участник)
    Route::get('/my-bids', [RfqController::class, 'myBids'])->name('bids.my');
    
    // Мои приглашения
    Route::get('/my-invitations', [RfqController::class, 'myInvitations'])->name('invitations.my');
    
    // Создание RFQ
    Route::get('/rfqs/create', [RfqController::class, 'create'])->name('rfqs.create');
    Route::post('/rfqs', [RfqController::class, 'store'])->name('rfqs.store');
    
    // Редактирование/удаление RFQ
    Route::get('/rfqs/{rfq}/edit', [RfqController::class, 'edit'])->name('rfqs.edit');
    Route::put('/rfqs/{rfq}', [RfqController::class, 'update'])->name('rfqs.update');
    Route::delete('/rfqs/{rfq}', [RfqController::class, 'destroy'])->name('rfqs.destroy');
    
    // Активация RFQ
    Route::post('/rfqs/{rfq}/activate', [RfqController::class, 'activate'])->name('rfqs.activate');
    
    // Подача заявки
    Route::post('/rfqs/{rfq}/bids', [RfqController::class, 'storeBid'])->name('rfqs.bids.store');
});

// ========================================================================
// AUCTIONS ROUTES
// ========================================================================

Route::prefix('auctions')->name('auctions.')->group(function () {
    // Публичные маршруты
    Route::get('/', [AuctionController::class, 'index'])->name('index');
    
    // ⚠️ ВАЖНО: Приватные маршруты с фиксированными путями ПЕРЕД {auction}
    Route::middleware('auth')->group(function () {
        // Личный кабинет (ДО {auction})
        Route::get('/my/list', [AuctionController::class, 'myAuctions'])->name('my');
        Route::get('/my/bids', [AuctionController::class, 'myBids'])->name('bids.my');
        Route::get('/my/invitations', [AuctionController::class, 'myInvitations'])->name('invitations.my');
        
        // Создание (ДО {auction})
        Route::get('/create', [AuctionController::class, 'create'])->name('create');
        Route::post('/', [AuctionController::class, 'store'])->name('store');
    });
    
    // Динамические маршруты с {auction} ПОСЛЕ всех фиксированных
    Route::get('/{auction}', [AuctionController::class, 'show'])->name('show');
    
    Route::middleware('auth')->group(function () {
        Route::get('/{auction}/edit', [AuctionController::class, 'edit'])->name('edit');
        Route::put('/{auction}', [AuctionController::class, 'update'])->name('update');
        Route::delete('/{auction}', [AuctionController::class, 'destroy'])->name('destroy');
        Route::post('/{auction}/activate', [AuctionController::class, 'activate'])->name('activate');
        Route::post('/{auction}/bids', [AuctionController::class, 'storeBid'])->name('bids.store');
        
        // Long Polling (JSON response)
        Route::get('/{auction}/state', [AuctionController::class, 'getState'])->name('state');
    });
});

// ========================================================================
// NEWS ROUTES
// ========================================================================

// Публичный доступ к новостям
Route::get('/news', [NewsController::class, 'index'])->name('news.index');

// Управление ключевыми словами (требует авторизации)
Route::middleware('auth')->group(function () {
    Route::get('/profile/keywords', [UserKeywordController::class, 'index'])
        ->name('profile.keywords.index');
    Route::post('/profile/keywords', [UserKeywordController::class, 'store'])
        ->name('profile.keywords.store');
    Route::delete('/profile/keywords/{keyword}', [UserKeywordController::class, 'destroy'])
        ->name('profile.keywords.destroy');
});

// ========================================================================
// AUTH ROUTES
// ========================================================================

require __DIR__.'/auth.php';