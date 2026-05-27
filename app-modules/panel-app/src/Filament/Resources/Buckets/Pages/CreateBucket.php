<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Filament\Resources\Buckets\Pages;

use Filament\Resources\Pages\CreateRecord;
use He4rt\PanelApp\Filament\Resources\Buckets\BucketResource;
use Illuminate\Support\Str;

class CreateBucket extends CreateRecord
{
    protected static string $resource = BucketResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data['token'] = Str::uuid()->toString();

        return $data;
    }
}
