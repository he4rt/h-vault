<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Filament\Resources\Buckets\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use He4rt\Vault\Albums\Album;
use Illuminate\Database\Eloquent\Model;

class AlbumsRelationManager extends RelationManager
{
    protected static string $relationship = 'albums';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Albums';
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Album Name')
                    ->searchable(),

                IconColumn::make('is_shared')
                    ->label('Shared')
                    ->boolean(),

                TextColumn::make('files_count')
                    ->counts('files')
                    ->label('Files')
                    ->badge(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime(timezone: config('app.viewable_timezone'))
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->using(fn (array $data, string $model): Album => $model::create($data))
                    ->mutateFormDataUsing(fn (array $data): array => array_merge($data, [
                        'share_token' => $data['is_shared'] ? str()->uuid() : null,
                    ])),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateFormDataUsing(fn (array $data): array => array_merge($data, [
                        'share_token' => $data['is_shared'] ? str()->uuid() : null,
                    ])),
                DeleteAction::make()
                    ->successNotification(null),
            ]);
    }

    protected function getFormSchema(): array
    {
        return [
            TextInput::make('name')
                ->label('Album Name')
                ->required()
                ->maxLength(255),

            Toggle::make('is_shared')
                ->label('Share album publicly')
                ->live(),
        ];
    }
}
