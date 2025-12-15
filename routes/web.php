<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\SocialiteController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RfqController;

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
});
// Публичный доступ к списку и просмотру компаний
Route::get('companies', [CompanyController::class, 'index'])->name('companies.index');
Route::get('companies/{company}', [CompanyController::class, 'show'])->name('companies.show');

// ========================================================================
// DASHBOARD & PROFILE ROUTES
// ========================================================================

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ========================================================================
// SOCIALITE AUTH ROUTES
// ========================================================================

Route::get('/auth/{provider}/redirect', [SocialiteController::class, 'redirect'])
    ->name('socialite.redirect');
Route::get('/auth/{provider}/callback', [SocialiteController::class, 'callback'])
    ->name('socialite.callback');

// Проекты (публичные: index, show; приватные: create, store, edit, update, destroy)
Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
Route::get('/projects/{project:slug}', [ProjectController::class, 'show'])->name('projects.show');
Route::get('/projects/{project:slug}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
Route::put('/projects/{project:slug}', [ProjectController::class, 'update'])->name('projects.update');
Route::delete('/projects/{project:slug}', [ProjectController::class, 'destroy'])->name('projects.destroy');

// Комментарии к проектам (требуют авторизации)
Route::middleware('auth')->group(function () {
    Route::post('/projects/{project:slug}/comments', [ProjectController::class, 'storeComment'])->name('projects.comments.store');
    Route::put('/comments/{comment}', [ProjectController::class, 'updateComment'])->name('comments.update');
    Route::delete('/comments/{comment}', [ProjectController::class, 'destroyComment'])->name('comments.destroy');
});

// RFQ Routes
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
    
    // Подача заявки
    Route::post('/rfqs/{rfq}/bids', [RfqController::class, 'storeBid'])->name('rfqs.bids.store');
});

// Публичные роуты
Route::get('/rfqs', [RfqController::class, 'index'])->name('rfqs.index');
Route::get('/rfqs/{rfq}', [RfqController::class, 'show'])->name('rfqs.show');

require __DIR__.'/auth.php';
