<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Add baseline security headers for all web responses.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // CSP is intentionally permissive enough for current Inertia/Vite behavior,
        // while still enforcing a safer default than no CSP at all.
        $directives = [
            "default-src 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "object-src 'none'",
            "img-src 'self' data: https:",
            "font-src 'self' data: https:",
            "style-src 'self' 'unsafe-inline' https:",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https:",
            "connect-src 'self' https: wss: ws:",
            "frame-src 'self' https://www.google.com https://www.gstatic.com https://accounts.google.com https://js.stripe.com",
        ];

        // Avoid forcing HTTPS in local HTTP development environments.
        if ($request->isSecure()) {
            $directives[] = 'upgrade-insecure-requests';
        }

        $csp = implode('; ', $directives);

        $response->headers->set('Content-Security-Policy', $csp);
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(self), microphone=(), geolocation=(), payment=()');

        // COOP is only effective on trustworthy origins (HTTPS or localhost).
        // Skip it for custom local HTTP domains (e.g., *.lvh.me) to avoid noisy browser warnings.
        $host = (string) $request->getHost();
        if ($request->isSecure() || in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        }

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
