<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\StripeClient;
use Stripe\Webhook;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;
use Stripe\Exception\SignatureVerificationException;
use UnexpectedValueException;

class StripeApiController extends Controller
{
    public function beginCheckout()
    {
        // 支払の開始
        // 購入者にメールを送る（支払方法について）
    }

    // 支払完了後の処理
    public function fulfillCheckout($session_id)
    {
        $key = config('stripe.stripe_secret_key');
        $stripe = new StripeClient($key);

        $session = $stripe
            ->checkout
            ->sessions
            ->retrieve($session_id, [
                'expand' => ['line_items']
            ]);

        try {
            DB::beginTransaction();
            $purchase = Purchase::lockForUpdate()
                ->where('session_id', $session_id)
                ->first();

            if (!$purchase) {
                throw new Exception('Purchase not found for session ID: ' . $session_id);
            }

            // 送られてきたセッションIDの取引の状態（purchasesテーブルのstatusではない）
            if ($session->payment_status !== 'unpaid') {
                $purchase->status = key(Purchase::PAID);
                $purchase->is_chat_enabled = true;

                // 同じ取引で複数回フルフィルメントが発生する可能性からsave()を使う
                $purchase->save();
                DB::commit();
            } else {
                throw new Exception('Payment status is unpaid for session ID: ' . $session_id);
            }
        } catch (Exception $e) {
            Log::error('error:'.$e->getMessage());
            Log::error('==========お客様支払い完了後のDB更新に失敗==========');
            Log::error('purchasesテーブルのstatusがprocessingのままです');
            Log::error('purchasesテーブルの情報');
            Log::error('id: '. $purchase->id . ' user_id: '. $purchase->buyer_id . ' item_id: '. $purchase->item_id);
            Log::error($e->getMessage());
            Log::error('=================================================');
            DB::rollBack();
        }
    }

    public function failCheckout()
    {
        // コンビニ支払の有効期限切れの処理
        // item_idがユニークなのを修正する（失敗時を考慮して複数OKとする）
        // statusをexpiredにする
    }

    // コンビニ支払用
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sig_header = $request->header('stripe-signature');
        $endpoint_secret = config('stripe.stripe_webhook_secret');
        $event = null;

        try {
            $event = Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
        } catch (UnexpectedValueException $e) {
            Log::error('UnexpectedValueException:'.$e->getMessage());
            return response('', 400);
        } catch (SignatureVerificationException $e) {
            Log::error('SignatureVerificationException:'.$e->getMessage());
            return response('', 400);
        }

        if ($event->type === 'checkout.session.completed') {
            Stripe::setApiKey(config('stripe.stripe_secret_key'));

            $paymentStatus = $event->data->object->payment_status ?? null;
            if ($paymentStatus && $paymentStatus === 'paid') {
                // カード支払完了
                $this->fulfillCheckout($event->data->object->id);
            } else {
                // コンビニ決済準備完了
                // buyerのインスタンスを作る
                $purchase = Purchase::with('user')
                    ->where('session_id', $event->data->object->id)->first();
                $buyer = $purchase->user;
                Log::info('buyer info.: '.$buyer);
                if ($buyer) {
                    Log::info('メール送信');
                    $buyer->test();
                }
                // メールを送る
            }

        } else if ($event->type === 'checkout.session.async_payment_succeeded') {
            // コンビニ支払完了
            $this->fulfillCheckout($event->data->object->id);
        } else if ($event->type === 'checkout.session.async_payment_failed') {
            // コンビニ支払失敗（期限切れ）
            Log::info('コンビニ決済支払失敗');
            // メール送信
        } else {
            Log::error('不明なイベントタイプが検出されました: '.$event->type);
        }
        return response('', 200);
    }
}
