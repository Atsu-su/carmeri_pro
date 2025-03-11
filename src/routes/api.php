<?php

use App\Http\Controllers\Api\ItemApiController;
use App\Http\Controllers\ChatController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// 画像スクロールロードのためのAPIルーティング（api/は自動で付与されるので不要）
Route::get('images', [ItemApiController::class, 'getImageApi']);
Route::get('count', [ItemApiController::class, 'getImageApi']);

// 認証済みのユーザのみapiにアクセス可能
Route::middleware(['auth:web', 'verified'])->group(function () {
    // チャット機能のAPIルーティング
    Route::post('chat/{chat_id}/read/{purchase_id}/{receiver_id}', [ChatController::class, 'read'])->name('chat.read');
    Route::post('chat/{chat_id}/update/{receiver_id}', [ChatController::class, 'update'])->name('chat.update');
    Route::post('chat/{chat_id}/delete/{receiver_id}', [ChatController::class, 'delete'])->name('chat.delete');
    Route::post('chat/image/{purchase_id}/{receiver_id}', [ChatController::class, 'sendImage'])->name('chat.send.image');
    Route::post('chat/{purchase_id}/{receiver_id}', [ChatController::class, 'sendMessage'])->name('chat.send')
        ->whereNumber('purchase_id')
        ->whereNumber('receiver_id');
});