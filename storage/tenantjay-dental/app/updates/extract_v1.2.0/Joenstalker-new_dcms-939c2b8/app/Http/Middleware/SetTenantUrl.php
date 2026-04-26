<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class SetTenantUrl
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (tenant()) {
            $hostname = $request->getHost();
            $scheme = $request->getScheme();
            $port = $request->getPort();
            
            $portStr = ($port && !in_array($port, [80, 443])) ? ":{$port}" : '';
            $baseUrl = "{$scheme}://{$hostname}{$portStr}";
            
            config(['app.url' => $baseUrl]);
            URL::forceRootUrl($baseUrl);
        }

        return $next($request);
    }
}
