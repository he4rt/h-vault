<?php

declare(strict_types=1);

namespace He4rt\Admin\Filament\Resources\BlockedIps\Pages;

use Filament\Resources\Pages\CreateRecord;
use He4rt\Admin\Filament\Resources\BlockedIps\BlockedIpResource;

class CreateBlockedIp extends CreateRecord
{
    protected static string $resource = BlockedIpResource::class;
}
