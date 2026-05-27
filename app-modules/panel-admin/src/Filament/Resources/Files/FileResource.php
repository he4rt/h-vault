<?php

declare(strict_types=1);

namespace He4rt\Admin\Filament\Resources\Files;

use App\Enums\NavigationGroup;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use He4rt\Admin\Filament\Resources\Files\Pages\ListFiles;
use He4rt\Admin\Filament\Resources\Files\Tables\FilesTable;
use He4rt\Vault\Files\File;
use UnitEnum;

class FileResource extends Resource
{
    protected static ?string $model = File::class;

    protected static ?string $slug = 'vault-files';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Vault;

    protected static ?int $navigationSort = 10;

    public static function table(Table $table): Table
    {
        return FilesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFiles::route('/'),
        ];
    }

    public static function getModelLabel(): string
    {
        return 'File';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Files';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['original_name', 'token'];
    }
}
