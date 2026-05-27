<?php

declare(strict_types=1);

namespace He4rt\Security\RateLimiting;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class VaultRateLimiter
{
    public function configure(): void
    {
        RateLimiter::for('vault-upload', function (Request $request) {
            $user = $request->user('sanctum') ?? auth('sanctum')->user();

            if ($user) {
                return Limit::perMinute(config('security.rate_limits.upload_authenticated'))
                    ->by($user->id);
            }

            return Limit::perMinute(config('security.rate_limits.upload_anonymous'))
                ->by($request->ip());
        });

        RateLimiter::for('vault-download', function (Request $request) {
            $user = $request->user('sanctum') ?? auth('sanctum')->user();

            if ($user) {
                return Limit::perMinute(config('security.rate_limits.download_authenticated'))
                    ->by($user->id);
            }

            return Limit::perMinute(config('security.rate_limits.download_anonymous'))
                ->by($request->ip());
        });

        RateLimiter::for('vault-auth', fn (Request $request) => Limit::perMinute(config('security.rate_limits.auth'))
            ->by($request->ip()));
    }
}
