<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Filament\Resources\Buckets\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BucketsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->default('Unnamed'),

                TextColumn::make('files_count')
                    ->counts('files')
                    ->label('Files')
                    ->badge(),

                TextColumn::make('albums_count')
                    ->counts('albums')
                    ->label('Albums')
                    ->badge(),

                IconColumn::make('share_token')
                    ->label('Shared')
                    ->boolean()
                    ->trueIcon('heroicon-o-globe-alt')
                    ->falseIcon('heroicon-o-lock-closed')
                    ->state(fn ($record): bool => $record->isShared()),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime(timezone: config('app.viewable_timezone'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->successNotification(null),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->successNotification(null),
                ]),
            ]);
    }
}
