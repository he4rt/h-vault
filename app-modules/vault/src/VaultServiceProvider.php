<?php

declare(strict_types=1);

namespace He4rt\Vault;

use He4rt\Vault\Files\File;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class VaultServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/vault.php', 'vault');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations/*');
        $this->loadRoutesFrom(__DIR__.'/../resources/routes/api.php');

        Relation::enforceMorphMap([
            'vault_file' => File::class,
        ]);
    }
}
