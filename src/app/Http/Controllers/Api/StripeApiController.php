<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Webhook;
use Stripe\Checkout\Session;

class StripeApiController extends Controller
{
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $signitureHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('stripe.stripe_webhook_secret');

        try {
            $event = Webhook::constructEvent(
                $payload,
                $signitureHeader,
                $endpointSecret
            );
        } catch (Exception $e) {
            Log::error('Webhook error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Webhook error',
                'message' => $e->getMessage()
            ], 400);
        }

        switch ($event->type) {
            case 'payment_intent.succeeded':
                $session = $event->data->object;
                \Log::info($session);
                break;
            default:
                \Log::info('どのタイプに当てはまりません');
                break;
        }

        return response()->json(['status' => 'success'], 200);
    }
}
