<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ItemApiController extends Controller
{
    public function getImageApi(Request $request)
    {
        $request->validate([
            'page' => 'required|integer',
            'limit' => 'required|integer',
        ]);

        if ($request->is('api/count*')) {
            try {
                $items = Item::query()
                    ->select('id', 'name', 'image', 'on_sale')
                    ->orderBy('id', 'asc')
                    ->paginate($request->limit);

                return response()->json($items->count());
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
                $items = Item::query()
                    ->select('id', 'name', 'image', 'on_sale')
                    ->orderBy('id', 'asc')
                    ->paginate($request->limit);

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
