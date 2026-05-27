<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Filament\Resources\Buckets\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use He4rt\PanelApp\Filament\Resources\Buckets\BucketResource;

class EditBucket extends EditRecord
{
    protected static string $resource = BucketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->successNotification(null),
        ];
    }
}
