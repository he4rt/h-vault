<?php

declare(strict_types=1);

use He4rt\Vault\Enums\FileStatus;

test('all statuses have labels', function (FileStatus $status): void {
    expect($status->getLabel())->toBeString()->not->toBeEmpty();
})->with(FileStatus::cases());

test('all statuses have colors', function (FileStatus $status): void {
    expect($status->getColor())->toBeArray()->not->toBeEmpty();
})->with(FileStatus::cases());

test('all statuses have icons', function (FileStatus $status): void {
    expect($status->getIcon())->not->toBeNull();
})->with(FileStatus::cases());
