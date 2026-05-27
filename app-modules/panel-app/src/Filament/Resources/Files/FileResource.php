<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Filament\Resources\Files;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use He4rt\PanelApp\Filament\Resources\Files\Pages\ListFiles;
use He4rt\PanelApp\Filament\Resources\Files\Tables\FilesTable;
use He4rt\Vault\Files\File;
use Illuminate\Database\Eloquent\Builder;
use Override;

class FileResource extends Resource
{
    protected static ?string $model = File::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocument;

    protected static ?string $navigationLabel = 'My Files';

    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return FilesTable::configure($table);
    }

    /**
     * @return Builder<File>
     */
    #[Override]
    public static function getEloquentQuery(): Builder
    {
        /** @var Builder<File> */
        return parent::getEloquentQuery()
            ->where('uploaded_by', auth()->id());
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
}
