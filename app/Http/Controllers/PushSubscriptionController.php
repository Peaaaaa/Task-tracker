<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PushSubscriptionController extends Controller
{
    /**
     * Return the public VAPID key to the browser.
     */
    public function publicKey()
    {
        return response()->json([
            'publicKey' => config('services.webpush.public_key'),
        ]);
    }

    /**
     * Save or update a browser push subscription.
     */
    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'endpoint' => 'required|url|max:2048',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
            'contentEncoding' => 'nullable|string|in:aes128gcm, aesgcm',
        ]);

        $subscription = PushSubscription::updateOrCreate(
            ['endpoint' => $validated['endpoint']],
            [
                'user_id' => null,
                'public_key' => $validated['keys']['p256dh'],
                'auth_token' => $validated['keys']['auth'],
                'content_encoding' => $validated['contentEncoding'] ?? 'aes128gcm',
            ]
        );

        return response()->json([
            'message' => 'Push subscription saved successfully.',
            'subscription_id' => $subscription->id,
        ], 201);
    }

    /**
     * Remove a browser push subscription.
     */
    public function unsubscribe(Request $request)
    {
        $validated = $request->validate([
            'endpoint' => 'required|url|max:2048',
        ]);

        PushSubscription::where('endpoint', $validated['endpoint'])->delete();

        return response()->json([
            'message' => 'Push subscription removed successfully.',
        ]);
    }
}
