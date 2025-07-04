<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Purchase;
use App\Models\User;
use App\Messages\Session as MessageSession;
use App\Messages\Message;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Stripe\Stripe;
use Stripe\Checkout\Session;

class PurchaseController extends Controller
{
    public function index($item_id)
    {
        $user = auth()->user();
        $item = Item::with('purchase')
            ->filterByUserStatus('items', 'seller_id')
            ->findOrFail($item_id);

        // 自分が出品した商品の場合は購入できない（リダイレクト）
        if ($item->isOwnItem()) {
            return redirect()
                ->route('item.show', ['item_id' => $item_id])
                ->with('message', Message::get('purchase.own'));
        }
        $message = MessageSession::exists('message');
        return view('purchase', compact('user', 'item', 'message'));
    }

    public function store($item_id)
    {
        $user = auth()->user();

        try {
            DB::beginTransaction();

            // nameのみ必要
            $item = Item::query()
                ->filterByUserStatus('items', 'seller_id')
                ->where('id', $item_id)
                ->where('on_sale', true)
                ->lockForUpdate()
                ->first();

            if (!$item) {
                return back()->with('message', Message::get('purchase.already'));
            }

            $item->update(['on_sale' => false]);

            $purchase = Purchase::create([
                'item_id' => $item->id,
                'buyer_id' => $user->id,
                'status' => Purchase::PROCESSING,
            ]);

            DB::commit();

            return $this->stripe($item, $user, $purchase);

        } catch (Exception $e) {
            Log::error($e->getMessage());
            DB::rollBack();
            return back()->with('message', Message::get('purchase.failed'));
        }
    }

    // このクラスのstoreメソッドで呼び出される
    public function stripe(Item $item, User $user, Purchase $purchase)
    {
        Stripe::setApiKey(config('stripe.stripe_secret_key'));
        $session = Session::create([
            // 必要最小限の情報のみ
            'metadata' => [
                'user_id' => $user->id,
                'order_id' => $purchase->id
            ],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'jpy',
                    'product_data' => [
                        'name' => $item->name
                    ],
                    'unit_amount' => $item->price
                ],
                'quantity' => 1
            ]],
            'mode' => 'payment',
            'success_url' => route('payment.success', ['purchase_id' => $purchase->id]),
            'cancel_url' => route('payment.cancel', ['purchase_id' => $purchase->id]),
        ]);

        return redirect($session->url);
    }

    public function success($purchase_id)
    {
        $item = Purchase::query()
            ->where('id', $purchase_id)
            ->first();

        try {
            $item->update(['status' => Purchase::PAID]);
        } catch (Exception $e) {
            Log::error('==========お客様支払い完了後のDB更新に失敗==========');
            Log::error('purchasesテーブルのstatusがprocessingのままです');
            Log::error('purchasesテーブルの情報');
            Log::error('id: '. $item->id . ' user_id: '. $item->buyer_id . ' item_id: '. $item->item_id);
            Log::error($e->getMessage());
            Log::error('=================================================');
        }

        return redirect()
            ->route('mypage')
            ->with('message', Message::get('purchase.success'));
    }

    public function cancel($purchase_id)
    {
            $item = Purchase::query()
            ->with('item')
            ->where('id', $purchase_id)
            ->first();

        try {
            $item->delete();
            $item->item->update(['on_sale' => true]);
        } catch (Exception $e) {
            Log::error('==========お客様支払いキャンセルのDB更新に失敗==========');
            Log::error('支払いがキャンセルされましたが、その後のDB更新処理に失敗しました');
            Log::error('purchasesテーブルのstatusがprocessingのままの可能性があります');
            Log::error('itemsテーブルのon_saleが0（false）のままの可能性があります');
            Log::error('purchasesテーブルの情報');
            Log::error('id: '. $item->id . ' user_id: '. $item->buyer_id . ' item_id: '. $item->item_id);
            Log::error($e->getMessage());
            Log::error('=================================================');
        }

        return redirect()
            ->route('mypage')
            ->with('message', Message::get('purchase.cancel'));
    }

    public function complete($purchase_id, $is_seller, $receiver_id)
    {
        $input = [
            'purchaseId' => $purchase_id,
            'isSeller' => $is_seller,
            'receiverId' => $receiver_id
        ];

        $rules = [
            'purchaseId' => 'required|integer|exists:purchases,id',
            'isSeller' => 'required|integer',
            'receiverId' => 'required|integer'
        ];

        $messages = [
            'purchaseId.required' => '値がありません',
            'purchaseId.integer' => '値が不正です',
            'purchaseId.exists' => '取引情報が見つかりません',
            'isSeller.required' => '値がありません',
            'isSeller.boolean' => '値が不正です',
            'receiverId.required' => '値がありません',
            'receiverId.integer' => '値が不正です'
        ];

        $validator = Validator::make($input, $rules, $messages);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $isSeller = $is_seller;
        $receiverId = $receiver_id;
        $user = auth()->user();

        // 対象の商品が自分が購入し相手が出品しているか確認
        try {
            Purchase::query()
                ->whereHas('item', function ($query) use ($receiverId) {
                    $query->where('seller_id', $receiverId);
                })
                ->where('buyer_id', $user->id)
                ->findOrFail($purchase_id);
        } catch (Exception $e) {
            abort(403);
        }

        // 出品者メールを送る処理
        if (!$isSeller) {
            // 出品者にメールを送る
            $seller = User::find($receiverId);
            $seller->sendEmailCompleteNotification();
        }

        try {
            Purchase::query()
                ->where('id', $purchase_id)
                ->update(['status' => Purchase::COMPLETED]);
            return response()->json(['success' => true]);
        } catch(Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['success' => false], 500);
        }
    }

    public function showStatus($purchase_id)
    {
        $user = auth()->user();

        // アクセスするURLによって場合分け
        if (request()->routeIs('status.seller.show')) {
            // 出品者閲覧用の購入者情報を取得
            $purchase = Purchase::query()
                ->filterByUserStatus('purchases', 'buyer_id')
                ->with(['user', 'item'])
                ->whereHas('item', function ($query) use ($user) {
                    $query->where('items.seller_id', $user->id);
                })
                ->findOrFail($purchase_id);

            $purchase->formattedCreatedAt = Carbon::parse($purchase->created_at)->format('Y年m月d日 H:i');
            $purchase->formattedShippedAt = $purchase->shipped_at == null ? '未発送' : Carbon::parse($purchase->shipped_at)->format('Y年m月d日 H:i');
            $purchase->isStatusChangeable = $purchase->status === key(Purchase::PAID) ? true : false;

            return view('item_status', compact('purchase'));
        } elseif (request()->routeIs('status.buyer.show')) {
            Log::info('購入者用：販売者の情報を表示');
            // 購入者閲覧用の販売者情報を取得
            $purchase = Purchase::query()
                ->filterByUserStatus('purchases', 'buyer_id')
                ->with(['item', 'item.user'])
                ->where('buyer_id', $user->id)
                ->findOrFail($purchase_id);

            $purchase->formattedCreatedAt = Carbon::parse($purchase->created_at)->format('Y年m月d日 H:i');
            $purchase->formattedShippedAt = $purchase->shipped_at == null ? '未発送' : Carbon::parse($purchase->shipped_at)->format('Y年m月d日 H:i');
            $purchase->isStatusChangeable = $purchase->status === key(Purchase::SHIPPED) ? true : false;

            return view('item_status_buyer', compact('purchase'));
        } else {
            abort(404);
        }
    }

    public function changeStatusToShipped($purchase_id)
    {
        $user = auth()->user();

        // ステータスを発送済みに変更
        $purchase = Purchase::query()
            ->filterByUserStatus('purchases', 'buyer_id')
            ->with(['item'])
            ->whereHas('item', function ($query) use ($user) {
                $query->where('items.seller_id', $user->id);
            })
            ->find($purchase_id);

        if (!$purchase) {
            return response()->json([
                'success' => false,
                'message' => '取引情報が見つかりません。'
            ], 404);
        }

        if ($purchase->status !== key(Purchase::PAID)) {
            return response()->json([
                'success' => false,
                'message' => '取引ステータスが「支払済」ではありません。'
            ], 400);
        }

        try {
            DB::beginTransaction();
            $purchase->update([
                'status' => key(Purchase::SHIPPED),
                'shipped_at' => Carbon::now() // 発送日時を現在時刻に設定
            ]);

            // 購入者に発送完了のメールを送る
            $buyer = User::find($purchase->buyer_id);
            $buyer->sendEmailStatusChangedToShippedNotification($purchase);

            DB::commit();

        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'サーバエラーです。しばらくしてから再度お試しください。'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'status' => $purchase->status_text,
            'shipped_at' => Carbon::parse($purchase->shipped_at)->format('Y年m月d日 H:i'),
        ]);
    }

    public function changeStatusToCompleted($purchase_id)
    {
        $user = auth()->user();

        // ステータスを発送済みに変更
            $purchase = Purchase::query()
                ->filterByUserStatus('purchases', 'buyer_id')
                ->with(['item.user', 'item'])
                ->where('buyer_id', $user->id)
                ->find($purchase_id);

            if (!$purchase) {
                return response()->json([
                    'success' => false,
                    'message' => '取引情報が見つかりません。'
                ], 404);
            }

            if ($purchase->status !== key(Purchase::SHIPPED)) {
                return response()->json([
                    'success' => false,
                    'message' => '取引ステータスが「発送済」ではありません。'
                ], 400);
            }

        try {
            $purchase->update([
                'status' => key(Purchase::COMPLETED),
            ]);

            // 出品者に発送完了のメールを送る
            $purchase->item->user->sendEmailStatusChangedToCompletedNotification($purchase);

        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'サーバエラーです。しばらくしてから再度お試しください。'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'status' => $purchase->status_text,
        ]);
    }
}
