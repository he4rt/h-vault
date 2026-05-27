<?php

declare(strict_types=1);

namespace He4rt\ContentPipeline\Antivirus\Contracts;

interface AntivirusScanner
{
    /**
     * Scan a file at the given path.
     *
     * @return bool true = clean, false = infected
     */
    public function scan(string $filePath): bool;

    /**
     * Returns a human-readable name identifying this scanner driver.
     */
    public function driverName(): string;
}
