<?php

declare(strict_types=1);

namespace He4rt\Admin\Filament\Resources\BlockedIps;

use App\Enums\NavigationGroup;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use He4rt\Admin\Filament\Resources\BlockedIps\Pages\CreateBlockedIp;
use He4rt\Admin\Filament\Resources\BlockedIps\Pages\EditBlockedIp;
use He4rt\Admin\Filament\Resources\BlockedIps\Pages\ListBlockedIps;
use He4rt\Admin\Filament\Resources\BlockedIps\Schemas\BlockedIpForm;
use He4rt\Admin\Filament\Resources\BlockedIps\Tables\BlockedIpsTable;
use He4rt\Security\IpBlocking\BlockedIp;
use UnitEnum;

class BlockedIpResource extends Resource
{
    protected static ?string $model = BlockedIp::class;

    protected static ?string $slug = 'blocked-ips';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldExclamation;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Security;

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return BlockedIpForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BlockedIpsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBlockedIps::route('/'),
            'create' => CreateBlockedIp::route('/create'),
            'edit' => EditBlockedIp::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return 'Blocked IP';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Blocked IPs';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['ip'];
    }
}
