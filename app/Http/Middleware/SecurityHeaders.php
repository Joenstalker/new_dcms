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
        $viteDevOrigins = $this->viteDevServerOrigins();

        $styleSrc = "style-src 'self' 'unsafe-inline' https:";
        $scriptSrc = "script-src 'self' 'unsafe-inline' 'unsafe-eval' https:";
        $connectSrc = "connect-src 'self' https: wss: ws: " . $request->getSchemeAndHttpHost();
        
        if (app()->environment('local')) {
            $connectSrc .= " https://*.ngrok-free.dev:* http://*.ngrok-free.dev:* https://*.ngrok-free.app:* http://*.ngrok-free.app:*";
        }

        $imgSrc = "img-src 'self' data: blob: https:";
        foreach ($viteDevOrigins as $origin) {
            $styleSrc .= ' '.$origin;
            $scriptSrc .= ' '.$origin;
            $connectSrc .= ' '.$origin;
            $imgSrc .= ' '.$origin;
        }

        $directives = [
            "default-src 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "object-src 'none'",
            $imgSrc,
            "font-src 'self' data: https:",
            $styleSrc,
            $scriptSrc,
            $connectSrc,
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

    /**
     * Origins for the Vite dev server (different host/port than the app) so CSP allows HMR scripts/styles.
     *
     * @return list<string>
     */
    private function viteDevServerOrigins(): array
    {
        if (! app()->environment('local')) {
            return [];
        }

        $hotPath = public_path('hot');
        if (! is_readable($hotPath)) {
            return [];
        }

        $url = trim((string) file_get_contents($hotPath));
        if ($url === '' || ! str_starts_with($url, 'http')) {
            return [];
        }

        $parts = parse_url($url);
        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return [];
        }

        $origin = $parts['scheme'].'://'.$parts['host'];
        if (isset($parts['port'])) {
            $origin .= ':'.$parts['port'];
        }

        return [$origin];
    }
}
