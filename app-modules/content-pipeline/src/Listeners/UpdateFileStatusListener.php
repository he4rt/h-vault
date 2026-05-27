<?php

declare(strict_types=1);

namespace He4rt\ContentPipeline\Listeners;

use He4rt\ContentPipeline\Events\FileScanCompleted;
use He4rt\Vault\Enums\FileStatus;

final class UpdateFileStatusListener
{
    public function handle(FileScanCompleted $event): void
    {
        $newStatus = $event->clean ? FileStatus::Clean : FileStatus::Infected;

        $event->file->changeStatus($newStatus);
    }
}
