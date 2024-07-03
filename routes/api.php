<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\RelationController;
use App\Http\Controllers\ImageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->prefix('user')->group(function () {
    Route::get('/', [AuthController::class, 'index'])->name('get.user');
    Route::get('/logout', [AuthController::class, 'logout'])->name('user.logout');
    Route::post('/change-info', [AuthController::class, 'changeInfo'])->name('user.change-info');

    Route::get('/list-available', [AuthController::class, 'listAvailable'])->name('user.list-available');
    Route::get('/search-users', [AuthController::class, 'searchUsers'])->name('user.search-users');
});

Route::middleware('auth:sanctum')->prefix('images')->group(function () {
    Route::post('/upload-profile', [ImageController::class, 'uploadProfileImage']);
    Route::post('/upload-avatar', [ImageController::class, 'uploadAvatarImage']);
});

Route::middleware('auth:sanctum')->prefix('relation')->group(function () {
    Route::post('/send-request', [RelationController::class, 'sendRequest'])->name('post.send-request');
    Route::get('/list-received-request', [RelationController::class, 'listReceivedRequest'])->name('post.list-received-request');
    Route::get('/list-connected', [RelationController::class, 'listConnected'])->name('post.list-connected');
    Route::delete('/{friendId}', [RelationController::class, 'relationDelete']);
});

Route::middleware('auth:sanctum')->prefix('message')->group(function () {
    Route::post('/history', [MessageController::class, 'historyMessage'])->name('history.message');
    Route::post('/store', [MessageController::class, 'store'])->name('store.message');
});

Route::post('/sign-up', [AuthController::class, 'signUp']);

Route::post('/login', [AuthController::class, 'login']);
