<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Filament\Resources\Buckets;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use He4rt\PanelApp\Filament\Resources\Buckets\Pages\CreateBucket;
use He4rt\PanelApp\Filament\Resources\Buckets\Pages\EditBucket;
use He4rt\PanelApp\Filament\Resources\Buckets\Pages\ListBuckets;
use He4rt\PanelApp\Filament\Resources\Buckets\RelationManagers\AlbumsRelationManager;
use He4rt\PanelApp\Filament\Resources\Buckets\Schemas\BucketForm;
use He4rt\PanelApp\Filament\Resources\Buckets\Tables\BucketsTable;
use He4rt\Vault\Buckets\Bucket;
use Illuminate\Database\Eloquent\Builder;
use Override;

class BucketResource extends Resource
{
    protected static ?string $model = Bucket::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static ?string $navigationLabel = 'My Buckets';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return BucketForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BucketsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            AlbumsRelationManager::make(),
        ];
    }

    /**
     * @return Builder<Bucket>
     */
    #[Override]
    public static function getEloquentQuery(): Builder
    {
        /** @var Builder<Bucket> */
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id());
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBuckets::route('/'),
            'create' => CreateBucket::route('/create'),
            'edit' => EditBucket::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return 'Bucket';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Buckets';
    }
}
