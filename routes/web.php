<?php

use App\Http\Controllers\AuctionController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanyJoinRequestController;
use App\Http\Controllers\CompanyModeratorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectMemberController;
use App\Http\Controllers\RfqController;
use App\Http\Controllers\TempUploadController;
use App\Http\Controllers\TenderController;
use App\Http\Controllers\UserKeywordController;
use Illuminate\Support\Facades\Route;

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
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return view('welcome');
});

// ========================================================================
// COMPANIES ROUTES
// ========================================================================

// Publicly accessible routes for viewing companies

// Authenticated routes for managing companies (create, store, edit, update, delete)
// CRUD компаний (создание, обновление, удаление только для авторизованных)
Route::middleware('auth')->group(function () {
    // F3: Временная загрузка файлов
    Route::post('/temp-upload', [TempUploadController::class, 'store'])->name('temp-upload.store');
    Route::delete('/temp-upload', [TempUploadController::class, 'destroy'])->name('temp-upload.destroy');
    Route::get('/temp-upload', [TempUploadController::class, 'index'])->name('temp-upload.index');

    // Using resource routes for authenticated actions (create, store, edit, update, destroy)
    // 'index' and 'show' are excluded here as they are defined above as public.
    Route::get('companies/create', [CompanyController::class, 'create'])->name('companies.create');
    Route::post('companies', [CompanyController::class, 'store'])->name('companies.store');
    Route::get('companies/{company}/edit', [CompanyController::class, 'edit'])->name('companies.edit');
    Route::put('companies/{company}', [CompanyController::class, 'update'])->name('companies.update');
    Route::delete('companies/{company}', [CompanyController::class, 'destroy'])->name('companies.destroy');
    Route::post('companies/{company}/photos', [CompanyController::class, 'uploadPhotos'])->name('companies.photos.upload');
    Route::delete('companies/{company}/photos/{media}', [CompanyController::class, 'deletePhoto'])->name('companies.photos.delete');

    // Запросы на присоединение к компании
    Route::get('/my-join-requests', [CompanyJoinRequestController::class, 'index'])
        ->name('join-requests.index');
    Route::post('/companies/{company}/join-requests', [CompanyJoinRequestController::class, 'store'])
        ->name('companies.join-requests.store');
    Route::delete('/join-requests/{joinRequest}', [CompanyJoinRequestController::class, 'destroy'])
        ->name('join-requests.destroy');
    Route::post('/join-requests/{joinRequest}/approve', [CompanyJoinRequestController::class, 'approve'])
        ->name('join-requests.approve');
    Route::post('/join-requests/{joinRequest}/reject', [CompanyJoinRequestController::class, 'reject'])
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

    Route::post('/posts', [PostController::class, 'store'])->name('posts.store');
    Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar.update');
    Route::delete('/profile/avatar', [ProfileController::class, 'destroyAvatar'])->name('profile.avatar.destroy');
    Route::post('/profile/feedback', [ProfileController::class, 'feedback'])->name('profile.feedback');
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

Route::middleware('auth')->group(function () {
    // ВАЖНО: create должен быть ДО {project:slug}, иначе Laravel пытается найти проект со slug "create"
    Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project:slug}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
    Route::put('/projects/{project:slug}', [ProjectController::class, 'update'])->name('projects.update');
    Route::delete('/projects/{project:slug}', [ProjectController::class, 'destroy'])->name('projects.destroy');

    // Комментарии к проектам
    Route::post('/projects/{project:slug}/comments', [ProjectController::class, 'storeComment'])->name('projects.comments.store');
    Route::put('/comments/{comment}', [ProjectController::class, 'updateComment'])->name('comments.update');
    Route::delete('/comments/{comment}', [ProjectController::class, 'destroyComment'])->name('comments.destroy');

    // Участники проекта (пользователи)
    Route::post('/projects/{project:slug}/members', [ProjectMemberController::class, 'store'])->name('projects.members.store');
    Route::put('/projects/{project:slug}/members/{user}', [ProjectMemberController::class, 'update'])->name('projects.members.update');
    Route::delete('/projects/{project:slug}/members/{user}', [ProjectMemberController::class, 'destroy'])->name('projects.members.destroy');

    // Запросы на присоединение к проекту
    Route::post('/projects/{project:slug}/join-requests', [ProjectMemberController::class, 'storeJoinRequest'])->name('projects.join-requests.store');
    Route::post('/project-join-requests/{joinRequest}/approve', [ProjectMemberController::class, 'approveJoinRequest'])->name('project-join-requests.approve');
    Route::post('/project-join-requests/{joinRequest}/reject', [ProjectMemberController::class, 'rejectJoinRequest'])->name('project-join-requests.reject');
    Route::delete('/project-join-requests/{joinRequest}', [ProjectMemberController::class, 'destroyJoinRequest'])->name('project-join-requests.destroy');
});

// Публичный просмотр проекта (после auth-группы, чтобы create не конфликтовал)
Route::get('/projects/{project:slug}', [ProjectController::class, 'show'])->name('projects.show');

// ========================================================================
// UNIFIED TENDERS ROUTES (RFQ + Аукционы в общем списке)
// ========================================================================

Route::get('/tenders', [TenderController::class, 'index'])->name('tenders.index');
Route::get('/tenders/rules', [TenderController::class, 'rules'])->name('tenders.rules');

Route::middleware(['auth'])->group(function () {
    Route::get('/my-tenders', [TenderController::class, 'myTenders'])->name('tenders.my');
    Route::get('/my-bids-all', [TenderController::class, 'myBids'])->name('tenders.bids.my');
    Route::get('/my-invitations-all', [TenderController::class, 'myInvitations'])->name('tenders.invitations.my');
});

// ========================================================================
// RFQ ROUTES
// ========================================================================

// Публичный список
Route::get('/rfqs', [RfqController::class, 'index'])->name('rfqs.index');

Route::middleware(['auth'])->group(function () {
    // Личный кабинет
    Route::get('/my-rfqs', [RfqController::class, 'myRfqs'])->name('rfqs.my');
    Route::get('/my-bids', [RfqController::class, 'myBids'])->name('bids.my');
    Route::get('/my-invitations', [RfqController::class, 'myInvitations'])->name('invitations.my');

    // ВАЖНО: create должен быть ДО {rfq}, иначе Laravel пытается найти RFQ со slug "create"
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

    // T8: Приглашение компании к участию
    Route::post('/rfqs/{rfq}/invitations', [RfqController::class, 'storeInvitation'])->name('rfqs.invitations.store');
});

// Публичный просмотр RFQ (после auth-группы, чтобы create не конфликтовал)
Route::get('/rfqs/{rfq}', [RfqController::class, 'show'])->name('rfqs.show');

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

        // Генерация протокола (для организатора)
        Route::post('/{auction}/protocol', [AuctionController::class, 'generateProtocol'])->name('protocol.generate');

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
// SEARCH ROUTES
// ========================================================================

use App\Http\Controllers\SearchController;

Route::get('/search', [SearchController::class, 'index'])->name('search.index');
Route::get('/search/quick', [SearchController::class, 'quick'])->name('search.quick');

// ========================================================================
// AUTH ROUTES
// ========================================================================

require __DIR__.'/auth.php';
