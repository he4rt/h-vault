<?php

declare(strict_types=1);

namespace He4rt\Admin\Filament\Resources\Files\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use He4rt\Vault\Enums\FileStatus;
use He4rt\Vault\Files\Actions\DeleteFileAction;
use He4rt\Vault\Files\File;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
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

                TextColumn::make('token')
                    ->label('Token')
                    ->searchable()
                    ->copyable()
                    ->limit(20)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('uploader.name')
                    ->label('Uploaded By')
                    ->default('Anonymous')
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
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(FileStatus::class),

                Filter::make('expired')
                    ->label('Expired')
                    ->query(fn (Builder $query) => $query->where('expires_at', '<', now())),
            ])
            ->recordActions([
                Action::make('quarantine')
                    ->label('Quarantine')
                    ->icon(Heroicon::OutlinedShieldExclamation)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (File $record): void {
                        $record->changeStatus(FileStatus::Quarantined);
                        $record->delete();
                    })
                    ->successNotification(null)
                    ->visible(fn (File $record): bool => $record->status !== FileStatus::Quarantined),

                DeleteAction::make()
                    ->action(function (File $record, DeleteFileAction $action): void {
                        $action->handle($record);
                    })
                    ->successNotification(null),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function (Collection $records, DeleteFileAction $action): void {
                            foreach ($records as $record) {
                                $action->handle($record);
                            }
                        })
                        ->successNotification(null),
                ]),
            ]);
    }
}
