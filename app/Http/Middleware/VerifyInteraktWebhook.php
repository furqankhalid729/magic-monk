<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyInteraktWebhook
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $payload = $request->getContent();
        $receivedSignature = $request->header('X-INTERAKT-SIGNATURE');
        $secret = env('INTERAKT_SECRET');

        if (!$receivedSignature || !$secret) {
            return response()->json(['error' => 'Signature or secret missing'], 400);
        }

        $computedSignature = hash_hmac('sha256', $payload, $secret);

        if (!hash_equals($computedSignature, $receivedSignature)) {
            return response()->json(['error' => 'Invalid webhook signature'], 401);
        }

        // Optional: validate topic
        $allowedTopics = ['message.delivered', 'user.created']; // customize this
        $topic = $request->input('topic');

        if ($topic && !in_array($topic, $allowedTopics)) {
            return response()->json(['error' => 'Invalid or unhandled topic'], 400);
        }

        return $next($request);
    }
}
