<?php

declare(strict_types=1);

namespace He4rt\ContentPipeline\Thumbnails;

use He4rt\ContentPipeline\Events\ThumbnailGenerated;
use He4rt\Vault\Files\File;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class GenerateThumbnailJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly File $file,
    ) {
        $this->onQueue('thumbnails');
    }

    public function handle(ThumbnailGenerator $generator): void
    {
        $disk = config('vault.storage.disk');

        $thumbnailPath = $generator->generate($this->file->storage_path, $disk);

        $this->file->update(['thumbnail_path' => $thumbnailPath]);

        event(new ThumbnailGenerated($this->file, $thumbnailPath));
    }
}
