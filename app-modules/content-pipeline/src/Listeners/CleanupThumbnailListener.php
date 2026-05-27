<?php

declare(strict_types=1);

namespace He4rt\ContentPipeline\Listeners;

use He4rt\Vault\Events\FileDeleted;
use He4rt\Vault\Events\FileExpired;
use Illuminate\Support\Facades\Storage;

final class CleanupThumbnailListener
{
    public function handle(FileExpired|FileDeleted $event): void
    {
        $thumbnailPathIsSet = $event->file->thumbnail_path !== null;

        if (!$thumbnailPathIsSet) {
            return;
        }

        Storage::disk(config('vault.storage.disk'))->delete($event->file->thumbnail_path);
    }
}
