<?php

declare(strict_types=1);

namespace He4rt\ContentPipeline\Thumbnails;

use He4rt\Vault\Events\FileUploaded;
use Illuminate\Contracts\Queue\ShouldQueue;

final class GenerateThumbnailListener implements ShouldQueue
{
    public string $queue = 'thumbnails';

    public function handle(FileUploaded $event): void
    {
        $isImageFile = str_starts_with($event->file->mime_type, 'image/');

        if (!$isImageFile) {
            return;
        }

        dispatch(new GenerateThumbnailJob($event->file));
    }
}
