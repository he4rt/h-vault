<?php

declare(strict_types=1);

namespace He4rt\Security;

use He4rt\Security\IpBlocking\BlockedIp;
use He4rt\Security\IpBlocking\IpBlockingMiddleware;
use He4rt\Security\RateLimiting\VaultRateLimiter;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class SecurityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/security.php', 'security');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations/*');

        resolve(VaultRateLimiter::class)->configure();

        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('vault.ip-block', IpBlockingMiddleware::class);

        Relation::enforceMorphMap([
            'blocked_ip' => BlockedIp::class,
        ]);
    }
}
