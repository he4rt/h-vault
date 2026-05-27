<?php

declare(strict_types=1);

namespace He4rt\ContentPipeline\Antivirus;

use He4rt\Vault\Events\FileUploaded;
use Illuminate\Contracts\Queue\ShouldQueue;

final class ScanFileListener implements ShouldQueue
{
    public string $queue = 'antivirus';

    public function handle(FileUploaded $event): void
    {
        dispatch(new ScanFileJob($event->file));
    }
}
