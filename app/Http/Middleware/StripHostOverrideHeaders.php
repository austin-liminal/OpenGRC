<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StripHostOverrideHeaders
{
    /**
     * Headers that can be used to override the Host value.
     *
     * These headers are stripped to prevent host override attacks including:
     * - Cache poisoning
     * - Password reset link manipulation
     * - Security control bypass
     *
     * Note: X-Forwarded-For is intentionally NOT stripped as it's needed for
     * client IP detection behind proxies and is handled by TrustProxies middleware.
     */
    protected array $dangerousHeaders = [
        'X-Forwarded-Host',
        'X-Host',
        'X-Forwarded-Server',
    ];

    /**
     * Handle an incoming request.
     *
     * Strips dangerous host override headers before they can be processed
     * by the application or framework.
     */
    public function handle(Request $request, Closure $next): Response
    {
        foreach ($this->dangerousHeaders as $header) {
            $request->headers->remove($header);
        }

        // Also remove from server superglobal to ensure complete removal
        foreach ($this->dangerousHeaders as $header) {
            $serverKey = 'HTTP_'.strtoupper(str_replace('-', '_', $header));
            $request->server->remove($serverKey);
        }

        return $next($request);
    }
}
