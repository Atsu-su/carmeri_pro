<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Like;
use App\Models\Purchase;
use App\Messages\Session as MessageSession;
use App\Models\Chat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index()
    {
        if (auth()->check()) {
            $user = auth()->user();

            $likedItems = Like::query()
                ->with('item')
                ->filterByUserStatus('likes')
                ->where('user_id', $user->id)
                ->whereHas('item', function ($query) use ($user) {
                    $query->where('items.seller_id', '!=', $user->id);
                })
                ->orderBy('item_id', 'desc')
                ->get();

            $message = MessageSession::exists('message');

            return view('index', compact('likedItems', 'message'));
        } else {
            $items = Item::orderBy('id', 'desc')->get();
            return view('index', compact('items'));
        }
    }

    public function myPageIndex()
    {
        $user = auth()->user();
        $user->rating = $user->evaluations > 0 ? round($user->rating_sum / $user->evaluations) : 0;
        $listedItems = Item::query()
            ->filterByUserStatus('items', 'seller_id')
            ->where('seller_id', $user->id)
            ->get();

        $purchasedItems = Purchase::query()
            ->with('item:id,name,image')
            ->filterByUserStatus('purchases', 'buyer_id')
            ->where('buyer_id', $user->id)
            ->get();

        $message = MessageSession::exists('message');

        // 共通のクエリ
        $validItems = Item::query()
            ->filterByUserStatusWithoutSelect('items', 'seller_id')
            ->select('items.id', 'items.seller_id');

        $chats = Chat::query()
        ->select('purchase_id', DB::raw('MAX(created_at) as latest_chat'))
        ->where('is_read', false)
        ->where('sender_id', '!=', $user->id)
        ->groupBy('purchase_id');

        // 1. 出品者の場合
        $sellerPurchases = Purchase::query()
            ->select('purchases.id')
            ->joinSub($validItems, 'valid_items', function ($query) {
                $query->on('purchases.item_id', '=', 'valid_items.id');
            })
            ->where('valid_items.seller_id', '=', $user->id)
            ->where('purchases.status', Purchase::PROCESSING);

        $sellingItemsPurchaseIds = DB::query()
            ->select('vp.id')
            ->fromSub($sellerPurchases, 'vp')
            ->leftJoinSub($chats, 'c', function ($join){
                $join->on('vp.id', '=', 'c.purchase_id');
            })
            ->orderByRaw("COALESCE(c.latest_chat, '1900-01-01') DESC")
            ->get()
            ->pluck('id')
            ->toArray();

        $sellingItems = Purchase::query()
            ->with(['item:id,name,price,image', 'chats' => function ($query) use ($user) {
                        $query->where('sender_id', '!=', $user->id)
                            ->where('is_read', false);
            }])
            ->whereIn('id', $sellingItemsPurchaseIds)
            ->get();

        // 2. 購入者の場合
        $buyerPurchases = Purchase::query()
            ->select('purchases.id')
            ->joinSub($validItems, 'valid_items', function ($query) {
                $query->on('purchases.item_id', '=', 'valid_items.id');
            })
            ->where('purchases.buyer_id', '=', $user->id)
            ->where('purchases.status', Purchase::PROCESSING);

        $purchasingItemsPurchaseIds = DB::query()
            ->select('vp.id')
            ->fromSub($buyerPurchases, 'vp')
            ->leftJoinSub($chats, 'c', function ($join){
                $join->on('vp.id', '=', 'c.purchase_id');
            })
            ->orderByRaw("COALESCE(c.latest_chat, '1900-01-01') DESC")
            ->get()
            ->pluck('id')
            ->toArray();

        $purchasingItems = Purchase::query()
            ->with(['item:id,name,price,image', 'chats' => function ($query) use ($user) {
                        $query->where('sender_id', '!=', $user->id)
                            ->where('is_read', false);
            }])
            ->whereIn('id', $purchasingItemsPurchaseIds)
            ->get();

        return view('mypage',
            compact(
                'user',
                'listedItems',
                'purchasedItems',
                'sellingItems',
                'purchasingItems',
                'message'
            )
        );
    }

    // 次ここから
    public function search(Request $request)
    {
        $keyword = $request->input('keyword');

        if (auth()->check()) {
            $user = auth()->user();

            $items = Item::query()
                ->where('name', 'like', "%$keyword%")
                ->where('seller_id', '!=', $user->id)
                ->orderBy('id', 'desc')
                ->get();

            $likedItems = Like::query()
                ->with('item')
                ->where('user_id', $user->id)
                ->whereHas('item', function ($query) use ($keyword, $user) {
                    $query->where('name', 'like', "%$keyword%")
                          ->where('seller_id', '!=', $user->id);
                })
                ->orderBy('item_id', 'desc')
                ->get();

            return view('index', compact('items', 'likedItems', 'keyword'));
        } else {
            $items = Item::query()
            ->where('name', 'like', "%$keyword%")
            ->orderBy('id', 'desc')
            ->get();

            return view('index', compact('items', 'keyword'));
        }
    }
}
