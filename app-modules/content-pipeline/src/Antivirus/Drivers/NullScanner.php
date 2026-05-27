<?php

declare(strict_types=1);

namespace He4rt\ContentPipeline\Antivirus\Drivers;

use He4rt\ContentPipeline\Antivirus\Contracts\AntivirusScanner;

/**
 * NullScanner is a no-op antivirus driver used in local/test environments.
 * It always reports files as clean (ADR-0005).
 */
final class NullScanner implements AntivirusScanner
{
    public function scan(string $filePath): bool
    {
        return true;
    }

    public function driverName(): string
    {
        return 'null';
    }
}
