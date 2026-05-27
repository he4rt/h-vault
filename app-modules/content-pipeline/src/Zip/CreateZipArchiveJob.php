<?php

declare(strict_types=1);

namespace He4rt\ContentPipeline\Zip;

use He4rt\Vault\Albums\Album;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class CreateZipArchiveJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Album $album,
    ) {
        $this->onQueue('zip');
    }

    public function handle(ZipArchiveService $service): void
    {
        $service->createFromAlbum($this->album);
    }
}
