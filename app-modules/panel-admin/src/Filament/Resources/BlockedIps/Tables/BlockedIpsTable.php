<?php

declare(strict_types=1);

namespace He4rt\Admin\Filament\Resources\BlockedIps\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use He4rt\Security\IpBlocking\BlockedIp;

class BlockedIpsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ip')
                    ->label('IP Address')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('reason')
                    ->label('Reason')
                    ->limit(50)
                    ->tooltip(fn (BlockedIp $record): string => $record->reason),

                TextColumn::make('blocked_until')
                    ->label('Blocked Until')
                    ->dateTime(timezone: config('app.viewable_timezone'))
                    ->sortable()
                    ->placeholder('Permanent'),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime(timezone: config('app.viewable_timezone'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
