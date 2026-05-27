<?php

declare(strict_types=1);

namespace He4rt\Vault;

use He4rt\Vault\Albums\Album;
use He4rt\Vault\Buckets\Bucket;
use He4rt\Vault\Files\File;
use He4rt\Vault\TTL\CleanExpiredFilesCommand;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class VaultServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/vault.php', 'vault');

        $this->commands([
            CleanExpiredFilesCommand::class,
        ]);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations/*');
        $this->loadRoutesFrom(__DIR__.'/../resources/routes/api.php');

        Relation::enforceMorphMap([
            'vault_file' => File::class,
            'vault_bucket' => Bucket::class,
            'vault_album' => Album::class,
        ]);
    }
}
