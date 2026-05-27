<?php

declare(strict_types=1);

namespace He4rt\ContentPipeline;

use He4rt\ContentPipeline\Antivirus\Contracts\AntivirusScanner;
use He4rt\ContentPipeline\Antivirus\Drivers\NullScanner;
use He4rt\ContentPipeline\Listeners\CleanupThumbnailListener;
use He4rt\Vault\Events\FileDeleted;
use He4rt\Vault\Events\FileExpired;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class ContentPipelineServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/content-pipeline.php', 'content-pipeline');

        $this->app->bind(AntivirusScanner::class, $this->resolveAntivirusDriver(...));
    }

    public function boot(): void
    {
        // CleanupThumbnailListener handles both FileExpired and FileDeleted.
        // Registered manually here because Laravel auto-discovery only maps
        // one event per listener via the handle() type-hint.
        Event::listen(FileExpired::class, CleanupThumbnailListener::class);
        Event::listen(FileDeleted::class, CleanupThumbnailListener::class);
    }

    private function resolveAntivirusDriver(): AntivirusScanner
    {
        // Additional drivers (e.g. 'clamav') can be added to the match as the project grows.
        return match (config('content-pipeline.antivirus.driver')) {
            default => new NullScanner(),
        };
    }
}
