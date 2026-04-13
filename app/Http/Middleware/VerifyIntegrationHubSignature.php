<?php
// DSA: app/Http/Middleware/VerifyIntegrationHubSignature.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyIntegrationHubSignature
{
    public function handle(Request $request, Closure $next)
    {
        // Skip in local/testing
        if (config('app.env') === 'local' || config('app.env') === 'testing') {
            return $next($request);
        }

        $signature = $request->header('X-IH-Signature');
        $timestamp = $request->header('X-IH-Timestamp');

        if (!$signature || !$timestamp) {
            return response()->json(['error' => 'Missing IH signature headers'], 401);
        }

        if (abs(time() - (int) $timestamp) > 300) {
            return response()->json(['error' => 'IH request expired'], 401);
        }

        $payload  = $timestamp . '.' . $request->getContent();
        $expected = hash_hmac('sha256', $payload, config('services.integration_hub.inbound_secret'));

        if (!hash_equals($expected, $signature)) {
            return response()->json(['error' => 'Invalid IH signature'], 401);
        }

        return $next($request);
    }
}