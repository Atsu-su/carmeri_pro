<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\StripeApiController;
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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Stripe\Stripe;
use Stripe\Checkout\Session;

class PurchaseController extends Controller
{
    public function checkValidPurchase($type, $purchase_id, $userId, $expectedStatus)
    {
        if ($type === 'seller') {
            // 出品者がステータスを変更する場合（支払済みから発送済みへ）
            $purchase = Purchase::query()
                ->filterByUserStatus('purchases', 'buyer_id')
                ->with(['item'])
                ->whereHas('item', function ($query) use ($userId) {
                    $query->where('items.seller_id', $userId);
                })
                ->find($purchase_id);
        } elseif ($type === 'buyer') {
            // 購入者がステータスを変更する場合（発送済みから完了へ）
            $purchase = Purchase::query()
                ->filterByUserStatus('purchases', 'buyer_id')
                ->with(['item.user', 'item'])
                ->where('buyer_id', $userId)
                ->find($purchase_id);
        }

        if (!$purchase) {
            return [
                'success' => false,
                'message' => '取引情報が見つかりません。',
                'status_code' => 404
            ];
        }

        if ($purchase->status !== key($expectedStatus)) {
            return [
                'success' => false,
                'message' => "取引ステータスが「{$expectedStatus[key($expectedStatus)]}」ではありません。",
                'status_code' => 400
            ];
        }
        return [
            'success' => true,
            'purchase' => $purchase,
            'status_code' => 200
        ];
    }

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
                ->where('items.id', $item_id)
                ->where('items.on_sale', true)
                ->lockForUpdate()
                ->first();

            if (!$item) {
                return back()->with('message', Message::get('purchase.already'));
            }

            $item->update(['on_sale' => false]);

            $purchase = Purchase::create([
                'item_id' => $item->id,
                'buyer_id' => $user->id,
                'status' => key(Purchase::PROCESSING),
            ]);

            $session = $this->stripe($item, $user, $purchase);

            $purchase->update(['session_id' => $session->id]);

            DB::commit();
            return redirect($session->url);
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
            'payment_intent_data' => [
                'metadata' => [
                    'user_id' => $user->id,
                    'purchase_id' => $purchase->id,
                ],
            ],
            'payment_method_types' => ['card', 'konbini'],
            'payment_method_options' => [
                'konbini' => [
                    'expires_after_days' => 7,
                ],
            ],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'jpy',
                    'product_data' => [
                        'name' => $item->name,
                        // 'image' => Storage::url('item/images/').$item->image // 画像のURLを取得
                    ],
                    'unit_amount' => $item->price
                ],
                'quantity' => 1
            ]],
            'mode' => 'payment',
            'success_url' => route('payment.success', ['purchase_id' => $purchase->id]).'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('payment.cancel', ['purchase_id' => $purchase->id]),
        ]);

        return $session;
    }

    public function success($purchase_id)
    {
        // ここでもfulfillCheckoutを実行する（Stripe推奨）
        $stripe = new StripeApiController();
        $stripe->fulfillCheckout(request()->query('session_id'));

        // ここでメールを送る処理を行う
        $seller = User::find($this->getSeller($purchase_id));
        $buyer = User::find($this->getBuyer($purchase_id));

        // メール送信

        return redirect()
            ->route('mypage')
            ->with('message', Message::get('purchase.success'));
    }

    public function cancel($purchase_id)
    {
            $purchase = Purchase::query()
            ->with('item')
            ->where('id', $purchase_id)
            ->first();

        try {
            $purchase->delete();
            $purchase->item->update(['on_sale' => true]);
        } catch (Exception $e) {
            Log::error('==========お客様支払いキャンセルのDB更新に失敗==========');
            Log::error('支払いがキャンセルされましたが、その後のDB更新処理に失敗しました');
            Log::error('purchasesテーブルのstatusがprocessingのままの可能性があります');
            Log::error('itemsテーブルのon_saleが0（false）のままの可能性があります');
            Log::error('purchasesテーブルの情報');
            Log::error('id: '. $purchase->id . ' user_id: '. $purchase->buyer_id . ' item_id: '. $purchase->item_id);
            Log::error($e->getMessage());
            Log::error('=================================================');
        }

        return redirect()
            ->route('index')
            ->with('message', Message::get('purchase.cancel'));
    }

    /**
     * Get the buyer ID for a given purchase ID.
     *
     * @param int $id (id of purchases)
     * @return int seller_id (seller_id of items)
     */
    public function getSeller($id)
    {
        try {
            $seller = Purchase::with('item')
                ->findOrFail($id);
        } catch (Exception $e) {
            Log::error('Error fetching seller: ' . $e->getMessage());
            return response('', 400);
        }

        return $seller->item->seller_id;
    }

    /**
     * Get the buyer ID for a given purchase ID.
     *
     * @param int $id (id of purchases)
     * @return int buyer_id (buyer_id of purchases)
     */
    public function getBuyer($id)
    {
        try {
            $buyer = Purchase::findOrFail($id);
        } catch (Exception $e) {
            Log::error('Error fetching buyer: ' . $e->getMessage());
            return response('', 400);
        }

        return $buyer->buyer_id;
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
        $expectedStatus = Purchase::PAID;
        $type = 'seller';
        $result = $this->checkValidPurchase($type, $purchase_id, $user->id, $expectedStatus);

        if (!$result['success']) {
            return response()->json([
                'success' => $result['success'],
                'message' => $result['message']
            ], $result['status_code']);
        }

        $purchase = $result['purchase'];

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
        $expectedStatus = Purchase::SHIPPED;
        $type = 'buyer';
        $result = $this->checkValidPurchase($type, $purchase_id, $user->id, $expectedStatus);

        if (!$result['success']) {
            return response()->json([
                'success' => $result['success'],
                'message' => $result['message']
            ], $result['status_code']);
        }

        $purchase = $result['purchase'];

        try {
            $purchase->update(['status' => key(Purchase::COMPLETED)]);

            // あわせてチャットを終了する場合、チャットを終了する
            if (request()->has('close_chat_checkbox')) {
                $purchase->update(['is_chat_enabled' => false]);
            }

            // 出品者に取引完了のメールを送る
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

    public function closeChat($purchase_id)
    {
        $user = auth()->user();
        $expectedStatus = Purchase::COMPLETED;
        $type = 'buyer'; // チャットを終了するのは購入者なので、buyerを指定
        $result = $this->checkValidPurchase($type, $purchase_id, $user->id, $expectedStatus);

        if (!$result['success']) {
            return response()->json([
                'success' => $result['success'],
                'message' => $result['message']
            ], $result['status_code']);
        }

        $purchase = $result['purchase'];

        try {
            // チャットを終了する
            $purchase->update(['is_chat_enabled' => false]);

            return response()->json([
                'success' => true,
                'message' => 'チャットを終了しました。',
            ]);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'サーバエラーです。しばらくしてから再度お試しください。'
            ], 500);
        }
    }
}
