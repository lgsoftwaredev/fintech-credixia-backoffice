<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class VerifyKycSignature
{
    public function handle(Request $request, Closure $next)
    {
        $secret = Config::get('services.metamap.webhook_secret');
        $signature = $request->header('X-KYC-Signature'); // adapta si MetaMap usa otro header
        $computed = base64_encode(hash_hmac('sha256', $request->getContent(), $secret, true));

        if (!$signature || !hash_equals($computed, $signature)) {
            abort(401, 'Invalid KYC signature');
        }
        return $next($request);
    }
}
