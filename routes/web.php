<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\SocialiteController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CompanyController;

Route::get('/', function () {
    return view('welcome');
});

// Публичный доступ к списку и просмотру компаний
Route::get('companies', [CompanyController::class, 'index'])->name('companies.index');
Route::get('companies/{company}', [CompanyController::class, 'show'])->name('companies.show');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // CRUD компаний (создание, обновление, удаление только для авторизованных)
    Route::post('companies', [CompanyController::class, 'store'])->name('companies.store');
    Route::get('companies/create', [CompanyController::class, 'create'])->name('companies.create');
    Route::get('companies/{company}/edit', [CompanyController::class, 'edit'])->name('companies.edit');
    Route::put('companies/{company}', [CompanyController::class, 'update'])->name('companies.update');
    Route::delete('companies/{company}', [CompanyController::class, 'destroy'])->name('companies.destroy');
});

Route::get('/auth/{provider}/redirect', [SocialiteController::class, 'redirect'])
    ->name('socialite.redirect');
Route::get('/auth/{provider}/callback', [SocialiteController::class, 'callback'])
    ->name('socialite.callback');

require __DIR__.'/auth.php';