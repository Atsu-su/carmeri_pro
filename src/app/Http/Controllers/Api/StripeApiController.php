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
use Stripe\Exception\SignatureVerificationException;
use UnexpectedValueException;

class StripeApiController extends Controller
{
    public function fulfillCheckout($session_id)
    {
        $key = config('stripe.stripe_secret_key');
        $stripe = new StripeClient($key);

        // TODO: Make sure fulfillment hasn't already been
        // performed for this Checkout Session

        $session = $stripe
            ->checkout
            ->sessions
            ->retrieve($session_id, [
                'expand' => ['line_items']
            ]);

        // TODO: Make this function safe to run multiple times,
        // even concurrently, with the same session ID
        // ・レコードロックでpurchasesテーブルを更新する
        // ・session_idとitem_idをキーにしてpurchaseのレコードを取得
        // ・取得できない場合はreturn response('', 400);で処理を終了させる
        try {
            DB::beginTransaction();
            $purchase = Purchase::lockForUpdate()
                ->where('session_id', $session_id)
                // ->where('item_id', $session->line_items->data[0]->price->product)
                ->first();

            if (!$purchase) {
                throw new Exception('Purchase not found for session ID: ' . $session_id);
            }

            // Check the Checkout Session's payment_status property
            // to determine if fulfillment should be performed
            if ($session->payment_status != 'unpaid') {
                // TODO: Perform fulfillment of the line items
                // ・取得したレコードのstatusをpaidに更新する
                $purchase->status = key(Purchase::PAID);
                $purchase->save();
                DB::commit();
            } else {
                throw new Exception('Payment status is unpaid for session ID: ' . $session_id);
            }
        } catch (Exception $e) {
            DB::rollBack();
        }

        // 不要かもしれないのでまずは$sessionの中身を見る
        // returnでsellerとbuyerのIDを返す（連想配列）
        // return [
        //     'seller_id' => $this->getSeller($purchase->id),
        //     'buyer_id' => $this->getBuyer($purchase->id)
        // ];
    }

    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sig_header = $request->header('stripe-signature');
        $endpoint_secret = config('stripe.stripe_webhook_secret');
        $event = null;

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload, $sig_header, $endpoint_secret
            );
        } catch (UnexpectedValueException $e) {
            return response('', 400);
        } catch (SignatureVerificationException $e) {
            return response('', 400);
        }

        if ($event->type === 'checkout.session.completed' ||
            $event->type === 'checkout.session.async_payment_succeeded') {
            // purchasesテーブルのレコードを更新する
            $users = $this->fulfillCheckout($event->data->object->id);
            // 購入者と出品者にメールを送信する
            // $eventの中にpurchase_idがあるかもしれないので、その場合は
            // fulfillCheckoutの返り値はvoidにする
        }
        return response('', 200);
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
}
