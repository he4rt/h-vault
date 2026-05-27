<?php

declare(strict_types=1);

namespace He4rt\Security\Listeners;

/**
 * Stub listener for future integration with content-pipeline's FileScanCompleted event.
 * When a file scan identifies a malicious upload, this listener will automatically
 * block the originating IP address.
 *
 * TODO: Implement when content-pipeline broadcasts FileScanCompleted with IP metadata.
 */
class BlockSuspiciousIpListener
{
    // public function handle(FileScanCompleted $event): void { ... }
}
