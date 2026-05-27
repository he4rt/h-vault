<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Filament\Resources\Files\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use He4rt\Vault\Enums\FileStatus;
use He4rt\Vault\Files\File;
use Illuminate\Support\Number;

class FilesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('original_name')
                    ->label('File Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('file_size')
                    ->label('Size')
                    ->state(fn (File $record): string => Number::fileSize($record->file_size))
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (FileStatus $state): array => $state->getColor())
                    ->formatStateUsing(fn (FileStatus $state): string => $state->getLabel()),

                TextColumn::make('mime_type')
                    ->label('Type')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime(timezone: config('app.viewable_timezone'))
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->dateTime(timezone: config('app.viewable_timezone'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->recordActions([
                Action::make('copy_link')
                    ->label('Copy Link')
                    ->icon(Heroicon::OutlinedLink)
                    ->action(fn () => null)
                    ->extraAttributes(fn (File $record): array => [
                        'x-on:click' => sprintf(
                            "navigator.clipboard.writeText('%s')",
                            route('vault.files.serve', ['token' => $record->token, 'filename' => $record->original_name])
                        ),
                    ]),
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
