<?php

declare(strict_types=1);

namespace He4rt\Admin\Filament\Resources\Files\Pages;

use Filament\Resources\Pages\ListRecords;
use He4rt\Admin\Filament\Resources\Files\FileResource;

class ListFiles extends ListRecords
{
    protected static string $resource = FileResource::class;
}
