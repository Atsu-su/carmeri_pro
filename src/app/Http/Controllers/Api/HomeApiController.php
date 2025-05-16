<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HomeApiController extends Controller
{
    public function getImageApi(Request $request)
    {
        $request->validate([
            'page' => 'required|integer',
            'limit' => 'required|integer',
        ]);

        if ($request->is('api/count*')) {
            $user = auth()->user();
            try {
                if (auth()->check()) {
                    $items = Item::query()
                        ->filterByUserStatusWithoutSelect('items', 'seller_id')
                        ->where('seller_id', '!=', $user->id)
                        ->select('items.id', 'items.name', 'items.image', 'items.on_sale')
                        ->orderBy('id', 'asc')
                        ->paginate($request->limit);

                    return response()->json($items->count());
                } else {
                    $items = Item::query()
                        ->filterByUserStatus('items', 'seller_id')
                        ->select('items.id', 'items.name', 'items.image', 'items.on_sale')
                        ->orderBy('id', 'asc')
                        ->paginate($request->limit);

                    return response()->json($items->count());
                }
            } catch (Exception $e) {
                Log::error($e->getMessage());
                return response()->json([
                    'error' => '画像の件数取得に失敗しました',
                    'message' => $e->getMessage()]
                , 500);
            }
        }

        if ($request->is('api/images*')) {
            try {
                if (auth()->check()) {
                    $user = auth()->user();
                    $items = Item::query()
                        ->filterByUserStatusWithoutSelect('items', 'seller_id')
                        ->where('seller_id', '!=', $user->id)
                        ->select('items.id', 'items.name', 'items.image', 'items.on_sale', 'items.price')
                        ->orderBy('id', 'asc')
                        ->paginate($request->limit);
                } else {
                    $items = Item::query()
                        ->filterByUserStatus('items', 'seller_id')
                        ->select('items.id', 'items.name', 'items.image', 'items.on_sale', 'items.price')
                        ->orderBy('id', 'asc')
                        ->paginate($request->limit);
                }

                return response()->json($items);
            } catch (Exception $e) {
                // エラーの詳細をログに出力
                Log::error($e->getMessage());
                return response()->json([
                    'error' => 'データの取得に失敗しました',
                    'message' => $e->getMessage()
                ], 500);
            }
        }

        return response()->json([
            'error' => '不正なリクエストです',
        ], 400);
    }
}
