<?php

declare(strict_types=1);

namespace He4rt\ContentPipeline\Antivirus;

use He4rt\ContentPipeline\Antivirus\Contracts\AntivirusScanner;
use He4rt\ContentPipeline\Events\FileScanCompleted;
use He4rt\ContentPipeline\Events\FileScanFailed;
use He4rt\Vault\Files\File;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

final class ScanFileJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly File $file,
    ) {
        $this->onQueue('antivirus');
    }

    public function handle(AntivirusScanner $scanner): void
    {
        try {
            $isClean = $scanner->scan($this->file->storage_path);

            event(new FileScanCompleted($this->file, $scanner->driverName(), $isClean));
        } catch (Throwable $throwable) {
            event(new FileScanFailed($this->file, $throwable->getMessage()));
        }
    }
}
