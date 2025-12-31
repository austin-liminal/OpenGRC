<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * @var array<int, string>|string|null
     */
    // This was done to support WAF Proxies. Where possible, this should be more specific.
    protected $proxies = '*';

    /**
     * The headers that should be used to detect proxies.
     *
     * Note: X-Forwarded-Host is intentionally excluded to prevent host override attacks.
     * Only X-Forwarded-For (for client IP), Port, and Proto are trusted.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO;
}
