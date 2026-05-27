<?php

declare(strict_types=1);

namespace He4rt\Security\IpBlocking;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class IpBlockingMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('security.ip_blocking.enabled')) {
            return $next($request);
        }

        $ip = $request->ip();
        $cacheKey = 'blocked_ip:'.$ip;
        $cacheTtl = config('security.ip_blocking.cache_ttl');

        $isBlocked = Cache::remember($cacheKey, $cacheTtl, function () use ($ip): bool {
            $blockedIp = BlockedIp::query()->where('ip', $ip)->first();

            if (!$blockedIp) {
                return false;
            }

            if ($blockedIp->isExpired()) {
                $blockedIp->delete();
                Cache::forget('blocked_ip:'.$ip);

                return false;
            }

            return true;
        });

        abort_if($isBlocked, Response::HTTP_FORBIDDEN, 'Your IP address has been blocked.');

        return $next($request);
    }
}
