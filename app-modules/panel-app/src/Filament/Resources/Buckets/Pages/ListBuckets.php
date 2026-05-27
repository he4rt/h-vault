<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Filament\Resources\Buckets\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use He4rt\PanelApp\Filament\Resources\Buckets\BucketResource;

class ListBuckets extends ListRecords
{
    protected static string $resource = BucketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
