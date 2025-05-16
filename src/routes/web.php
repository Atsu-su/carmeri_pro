<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\UserController;
use GuzzleHttp\Psr7\Request;
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

Route::middleware('header')->group(function () {
    // 詳細検索表示のために必要なデータを取得するミドルウェア
    Route::middleware('search')->group(function () {
        Route::get('/search/advanced', [SearchController::class, 'advancedSearch'])->name('search.advanced');
        Route::post('/search/advanced', [SearchController::class, 'advancedSearch'])->name('search.advanced');
        Route::get('/search', [SearchController::class, 'search'])->name('search');
        Route::post('/search', [SearchController::class, 'search'])->name('search');
        Route::get('/', [HomeController::class, 'index'])->name('index');
    });
    Route::get('/item/{item_id}', [ItemController::class, 'show'])->name('item.show');
    Route::get('activate', [UserController::class, 'inputEmail'])->name('activate.index');
    Route::post('activate', [UserController::class, 'activateUser'])->name('activate');
    Route::middleware(['auth', 'verified'])->group(function () {
        // 詳細検索表示のために必要なデータを取得するミドルウェア
        Route::middleware('search')->group(function () {
            Route::get('favorite', [HomeController::class, 'favorite'])->name('favorite');
            Route::get('favorite/search/advanced', [SearchController::class, 'favoriteAdvancedSearch'])->name('favorite.search.advanced');
            Route::post('favorite/search/advanced', [SearchController::class, 'favoriteAdvancedSearch'])->name('favorite.search.advanced');
        });
        Route::get('mypage', [HomeController::class, 'myPageIndex'])->name('mypage');
        // ------------------------------------------------------------------------------------------------
        // 登録時：register/profile 情報変更時：mypage/profile
        Route::get('register/profile', [ProfileController::class, 'edit'])->name('register.profile.edit');
        Route::get('mypage/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        // ------------------------------------------------------------------------------------------------
        Route::post('mypage/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::post('item/{item_id}/like', [LikeController::class, 'toggleLike'])->name('like');
        Route::post('item/{item_id}/comment/update/{comment_id}', [CommentController::class, 'update'])->name('comment.update');
        Route::post('item/{item_id}/comment/delete/{comment_id}', [CommentController::class, 'delete'])->name('comment.delete');
        Route::post('item/{item_id}/comment', [CommentController::class, 'store'])->name('comment.store');
        Route::post('purchase/{purchase_id}/complete/{is_seller}/{receiver_id}', [PurchaseController::class, 'complete'])->name('purchase.complete');
        Route::get('purchase/address/{item_id}', [AddressController::class, 'edit'])->name('address.edit');
        Route::post('purchase/address/{item_id}', [AddressController::class, 'update'])->name('address.update');
        Route::get('purchase/{item_id}', [PurchaseController::class, 'index'])->name('purchase');
        Route::post('purchase/{item_id}', [PurchaseController::class, 'store'])->name('purchase.store');
        Route::get('sell/edit/{item_id}', [ItemController::class, 'edit'])->name('sell.edit');
        Route::post('sell/update/{item_id}', [ItemController::class, 'update'])->name('sell.update');
        Route::delete('sell/delete/{item_id}', [ItemController::class, 'delete'])->name('sell.delete');
        Route::get('sell', [ItemController::class, 'create'])->name('sell.create');
        Route::post('sell', [ItemController::class, 'store'])->name('sell.store');
        // 評価のためのルート
        Route::post('rating/{seller_id}', [UserController::class, 'rating'])->name('user.rating');
        // ユーザ無効化・有効化
        Route::get('activate/profile/password', [UserController::class, 'editPassword'])->name('activate.profile.edit');
        Route::put('activate/profile/password', [UserController::class, 'updatePassword'])->name('activate.profile.update');
        Route::put('user/deactivate', [UserController::class, 'deactivateUser'])->name('user.deactivate');
        // チャット関連
        Route::get('chat/{purchase_id}', [ChatController::class, 'index'])->name('chat');
        // ------------------------------------------------------------------------------------------------
        // stripeの成功・キャンセル用ルーティング
        Route::get('payment/success/{purchase_id}', [PurchaseController::class, 'success'])->name('payment.success');
        Route::get('payment/cancel/{purchase_id}', [PurchaseController::class, 'cancel'])->name('payment.cancel');
        // ------------------------------------------------------------------------------------------------
    });
});