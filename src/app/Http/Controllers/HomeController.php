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
            $message = MessageSession::exists('message');
            return view('index', compact('message'));
        } else {
            $items = Item::orderBy('id', 'desc')->get();
            return view('index', compact('items'));
        }
    }

    public function favorite() {
        $user = auth()->user();
        $likedItems = Like::query()
            ->with('item')
            ->filterByUserStatus('likes')
            ->where('user_id', $user->id)
            ->whereHas('item', function ($query) use ($user) {
                $query->where('items.seller_id', '!=', $user->id);
            })
            ->orderBy('item_id', 'desc')
            ->paginate(10);

        return view('favorite', compact('likedItems'));
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

    // public function search(Request $request)
    // {
    //     // middlewareに変更予定（Middleware/Search.phpを作成済み）
    //     // 詳細検索用のデータ
    //     $conditions = Condition::all();
    //     $categories = Category::all();

    //     // 検索の場合はスクロールなし、ページネーションありにする
    //     $searchFlag = true;
    //     $keyword = $request->input('keyword');

    //     if (auth()->check()) {
    //         $user = auth()->user();

    //         $items = Item::query()
    //             ->filterByUserStatus('items', 'seller_id')
    //             ->where('items.name', 'like', "%$keyword%")
    //             ->where('items.seller_id', '!=', $user->id)
    //             ->orderBy('id', 'desc')
    //             ->paginate(10);

    //         $likedItems = Like::query()
    //             ->with('item')
    //             ->filterByUserStatus('likes')
    //             ->where('likes.user_id', $user->id)
    //             ->whereHas('item', function ($query) use ($keyword, $user) {
    //                 $query->where('items.name', 'like', "%$keyword%")
    //                       ->where('items.seller_id', '!=', $user->id);
    //             })
    //             ->orderBy('item_id', 'desc')
    //             ->paginate(10);

    //         return view('index', compact('items', 'likedItems', 'keyword', 'searchFlag'))
    //             // middleware導入後削除予定
    //             ->with([
    //                 'conditions' => $conditions,
    //                 'categories' => $categories,
    //             ]);
    //         } else {
    //             $items = Item::query()
    //                 ->filterByUserStatus('items', 'seller_id')
    //                 ->where('items.name', 'like', "%$keyword%")
    //                 ->orderBy('items.id', 'desc')
    //                 ->paginate(10);

    //             return view('index', compact('items', 'keyword', 'searchFlag'))
    //             ->with([
    //                 // middleware導入後削除予定
    //                 'conditions' => $conditions,
    //                 'categories' => $categories,
    //             ]);
    //     }
    // }
}
