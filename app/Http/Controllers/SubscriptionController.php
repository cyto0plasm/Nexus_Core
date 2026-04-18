<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
     // Return available plans and current user status
    public function plans(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'plans' => [
                'monthly' => [
                    'name'       => 'Monthly',
                    'price'      => config('subscription.monthly_price'),
                    'currency'   => 'EGP',
                    'duration'   => '1 month',
                ],
                'yearly' => [
                    'name'       => 'Yearly',
                    'price'      => config('subscription.yearly_price'),
                    'currency'   => 'EGP',
                    'duration'   => '1 year',
                    'savings'    => round(100 - (config('subscription.yearly_price') / (config('subscription.monthly_price') * 12)) * 100) . '%',
                ],
            ],
            'status' => [
                'on_trial'             => $user->onTrial(),
                'trial_ends_at'        => $user->trial_ends_at,
                'is_subscribed'        => $user->isSubscribed(),
                'subscription_ends_at' => $user->subscription_ends_at,
                'plan'                 => $user->subscription_plan,
            ],
        ]);
    }

    // Step 1: Initiate payment — returns Paymob iframe URL
    public function subscribe(Request $request)
    {
        $request->validate([
            'plan' => ['required', 'in:monthly,yearly'],
        ]);

        $user  = $request->user();
        $plan  = $request->plan;
        $price = $plan === 'monthly'
            ? config('subscription.monthly_price')
            : config('subscription.yearly_price');

        // Paymob expects amount in cents (piastres)
        $amountCents = $price * 100;

        // --- Step 1: Authenticate with Paymob and get auth token ---
        $authResponse = Http::post(config('paymob.base_url') . '/auth/tokens', [
            'api_key' => config('paymob.api_key'),
        ]);

        if ($authResponse->failed()) {
            Log::error('Paymob auth failed', $authResponse->json());
            return response()->json(['message' => 'Payment service unavailable'], 503);
        }

        $authToken = $authResponse->json('token');

        // --- Step 2: Register an order on Paymob ---
        $orderResponse = Http::post(config('paymob.base_url') . '/ecommerce/orders', [
            'auth_token'     => $authToken,
            'delivery_needed' => false,
            'amount_cents'   => $amountCents,
            'currency'       => 'EGP',
            'items'          => [],
            'merchant_order_id' => 'nexus_' . $user->id . '_' . time(),
        ]);

        if ($orderResponse->failed()) {
            Log::error('Paymob order creation failed', $orderResponse->json());
            return response()->json(['message' => 'Could not create payment order'], 503);
        }

        $paymobOrderId = $orderResponse->json('id');

        // --- Step 3: Get a payment key (iframe token) ---
        $keyResponse = Http::post(config('paymob.base_url') . '/acceptance/payment_keys', [
            'auth_token'     => $authToken,
            'amount_cents'   => $amountCents,
            'expiration'     => 3600,
            'order_id'       => $paymobOrderId,
            'currency'       => 'EGP',
            'integration_id' => config('paymob.integration_id'),
            'billing_data'   => [
                'first_name'       => $user->name,
                'last_name'        => 'N/A',
                'email'            => $user->email,
                'phone_number'     => $user->phone ?? 'N/A',
                'apartment'        => 'N/A',
                'floor'            => 'N/A',
                'street'           => 'N/A',
                'building'         => 'N/A',
                'shipping_method'  => 'N/A',
                'postal_code'      => 'N/A',
                'city'             => 'N/A',
                'country'          => 'EG',
                'state'            => 'N/A',
            ],
        ]);

        if ($keyResponse->failed()) {
            Log::error('Paymob payment key failed', $keyResponse->json());
            return response()->json(['message' => 'Could not initiate payment'], 503);
        }

        $paymentKey = $keyResponse->json('token');

        // Store a pending subscription so we can update it in the webhook
        Subscription::create([
            'user_id'          => $user->id,
            'paymob_order_id'  => $paymobOrderId,
            'plan'             => $plan,
            'amount'           => $price,
            'status'           => 'pending',
        ]);

        return response()->json([
           'payment_url' => "https://accept.paymob.com/unifiedcheckout/?publicKey=" . config('paymob.public_key') . "&clientSecret={$paymentKey}",

        ]);
    }

    // Step 2: Paymob calls this webhook after payment
    public function webhook(Request $request)
    {
        $data = $request->all();

        // --- Verify HMAC to make sure this is actually from Paymob ---
        $hmac       = $request->query('hmac');
        $computed   = $this->computeHmac($data);

        if ($hmac !== $computed) {
            Log::warning('Paymob webhook HMAC mismatch');
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $transactionId = $data['obj']['id'] ?? null;
        $paymobOrderId = $data['obj']['order']['id'] ?? null;
        $success       = $data['obj']['success'] ?? false;

        if (!$success || !$paymobOrderId) {
            return response()->json(['message' => 'Payment not successful']);
        }

        // Find the pending subscription for this order
        $subscription = Subscription::where('paymob_order_id', $paymobOrderId)
            ->where('status', 'pending')
            ->first();

        if (!$subscription) {
            Log::warning('Paymob webhook: no pending subscription for order ' . $paymobOrderId);
            return response()->json(['message' => 'Subscription not found'], 404);
        }

        $startsAt = now();
        $endsAt   = $subscription->plan === 'monthly'
            ? now()->addMonth()
            : now()->addYear();

        // Activate the subscription
        $subscription->update([
            'paymob_transaction_id' => $transactionId,
            'status'                => 'active',
            'starts_at'             => $startsAt,
            'ends_at'               => $endsAt,
        ]);

        // Update the user so middleware checks are fast
        $subscription->user->update([
            'subscribed_at'        => $startsAt,
            'subscription_ends_at' => $endsAt,
            'subscription_plan'    => $subscription->plan,
        ]);

        return response()->json(['message' => 'Subscription activated']);
    }

    // Compute HMAC exactly as Paymob expects
    private function computeHmac(array $data): string
    {
        $obj = $data['obj'];

        // Paymob's HMAC concatenation order (must be exact)
        $string = implode('', [
            $obj['amount_cents'],
            $obj['created_at'],
            $obj['currency'],
            $obj['error_occured'],
            $obj['has_parent_transaction'],
            $obj['id'],
            $obj['integration_id'],
            $obj['is_3d_secure'],
            $obj['is_auth'],
            $obj['is_capture'],
            $obj['is_refunded'],
            $obj['is_standalone_payment'],
            $obj['is_voided'],
            $obj['order']['id'],
            $obj['owner'],
            $obj['pending'],
            $obj['source_data']['pan'],
            $obj['source_data']['sub_type'],
            $obj['source_data']['type'],
            $obj['success'],
        ]);

        return hash_hmac('sha512', $string, config('paymob.hmac_secret'));
    }

}
