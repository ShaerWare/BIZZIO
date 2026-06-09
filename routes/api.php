<?php

use App\Http\Controllers\Api\V1\ChatController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/chat', [ChatController::class, 'handle']);
});
