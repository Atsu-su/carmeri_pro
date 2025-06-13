<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Like;
use App\Models\Purchase;
use App\Messages\Session as MessageSession;
use App\Models\Chat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class HomeController extends Controller
{
    public function getImageUrl($image)
    {
        // 画像の存在確認と画像URLの取得
        if ($image && Storage::exists('item_images/'.$image)) {
            return Storage::url('item_images/'.$image);
        }
        return asset('img/no_image.jpg'); // 画像が存在しない場合のURL
    }

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
            ->paginate(5, ['*'], 'listed_items_page');

        $purchasedItems = Purchase::query()
            ->with([
                'item:id,name,image,price,seller_id',
                'item.user:id,name,image'
            ])
            ->filterByUserStatus('purchases', 'buyer_id')
            ->where('buyer_id', $user->id)
            ->paginate(5, ['*'], 'purchased_items_page');

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
            ->with([
                'item:id,name,price,image',
                'chats' => function ($query) use ($user) {
                        $query->where('sender_id', '!=', $user->id)
                            ->where('is_read', false);
                },
                'user:id,name,image'
            ])
            ->whereIn('id', $sellingItemsPurchaseIds)
            ->paginate(5, ['*'], 'selling_items_page');

        $sellingItems->getCollection()->transform(function ($purchase) {
            $purchase->chats->chats_count = $purchase->chats->count();
            return $purchase;
        });

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
            ->with([
                'item:id,name,price,image,seller_id',
                'item.user:id,name',
                'chats' => function ($query) use ($user) {
                        $query->where('sender_id', '!=', $user->id)
                            ->where('is_read', false);
            }])
            ->whereIn('id', $purchasingItemsPurchaseIds)
            ->paginate(5, ['*'], 'purchasing_items_page');

        $purchasingItems->getCollection()->transform(function ($purchase) {
            $purchase->chats->chats_count = $purchase->chats->count();
            return $purchase;
        });

        // ajaxリクエストの場合（mypageのページネーションからのリクエストなど）
        if (request()->ajax()) {
            if (request()->has('listed_items_page')) {
                // 出品中の商品
                $listedItems->getCollection()->transform(function ($item) {
                    $item->image_url = $this->getImageUrl($item->image);
                    $item->on_sale_text = $item->on_sale_text;
                    return $item;
                });

                // ページネーションのHTMLを取得
                $pagination = $listedItems->links('vendor.pagination.default')->toHtml();

                return response()->json([
                    'type' => 'listed_items',
                    'items' => $listedItems,
                    'pagination' => $pagination,
                ]);
            } elseif (request()->has('purchased_items_page')) {
                // 購入済み商品
                $purchasedItems->getCollection()->transform(function ($item){
                    $item->item->image_url = $this->getImageUrl($item->item->image);    // imageUrlプロパティを追加
                    return $item;
                });

                // ページネーションのHTMLを取得
                $pagination = $purchasedItems->links('vendor.pagination.default')->toHtml();

                return response()->json([
                    'type' => 'purchased_items',
                    'items' => $purchasedItems,
                    'pagination' => $pagination
                ]);
            } elseif (request()->has('selling_items_page')) {
                // 購入手続き中の出品商品
                $sellingItems->getCollection()->transform(function ($purchase) {
                    $purchase->item->image_url = $this->getImageUrl($purchase->item->image);
                    $purchase->status_text = $purchase->status_text;
                    $purchase->chats->chats_count = $purchase->chats->count();
                    return $purchase;
                });

                // ページネーションのHTMLを取得
                $pagination = $sellingItems->links('vendor.pagination.default')->toHtml();

                return response()->json([
                    'type' => 'selling_items',
                    'items' => $sellingItems,
                    'pagination' => $pagination
                ]);
            } elseif (request()->has('purchasing_items_page')) {
                // 購入手続き中の商品
                $purchasingItems->getCollection()->transform(function ($purchase) {
                    $purchase->item->image_url = $this->getImageUrl($purchase->item->image);
                    $purchase->status_text = $purchase->status_text;
                    $purchase->chats->chats_count = $purchase->chats->count();
                    return $purchase;
                });

                // ページネーションのHTMLを取得
                $pagination = $purchasingItems->links('vendor.pagination.default')->toHtml();

                return response()->json([
                    'type' => 'purchasing_items',
                    'items' => $purchasingItems,
                    'pagination' => $pagination
                ]);
            } else {
                return response()->json([]);
            }
        }

        // 通常のリクエストの場合
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
}
