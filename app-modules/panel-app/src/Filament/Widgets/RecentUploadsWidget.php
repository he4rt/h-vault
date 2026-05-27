<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Filament\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget;
use He4rt\Vault\Enums\FileStatus;
use He4rt\Vault\Files\File;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;

class RecentUploadsWidget extends TableWidget
{
    protected static ?string $heading = 'Recent Uploads';

    protected int|string|array $columnSpan = 'full';

    /**
     * @return Builder<File>
     */
    protected function getTableQuery(): Builder
    {
        return File::query()
            ->where('uploaded_by', auth()->id())
            ->latest()
            ->limit(10);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('original_name')
                ->label('File Name')
                ->searchable(),

            TextColumn::make('file_size')
                ->label('Size')
                ->state(fn (File $record): string => Number::fileSize($record->file_size)),

            TextColumn::make('status')
                ->label('Status')
                ->badge()
                ->color(fn (FileStatus $state): array => $state->getColor())
                ->formatStateUsing(fn (FileStatus $state): string => $state->getLabel()),

            TextColumn::make('expires_at')
                ->label('Expires')
                ->dateTime(timezone: config('app.viewable_timezone'))
                ->placeholder('Never'),

            TextColumn::make('created_at')
                ->label('Uploaded')
                ->dateTime(timezone: config('app.viewable_timezone'))
                ->sortable(),
        ];
    }
}
