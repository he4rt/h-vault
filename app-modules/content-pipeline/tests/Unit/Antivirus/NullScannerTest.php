<?php

declare(strict_types=1);

use He4rt\ContentPipeline\Antivirus\Drivers\NullScanner;

test('NullScanner always returns true for any file path', function (): void {
    $scanner = new NullScanner();

    expect($scanner->scan('/some/path/to/file.png'))->toBeTrue()
        ->and($scanner->scan('/tmp/malicious.exe'))->toBeTrue()
        ->and($scanner->scan(''))->toBeTrue();
});

test('NullScanner driver name is null', function (): void {
    $scanner = new NullScanner();

    expect($scanner->driverName())->toBe('null');
});
