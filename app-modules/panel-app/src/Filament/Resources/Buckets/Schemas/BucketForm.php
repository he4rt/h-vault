<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Filament\Resources\Buckets\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use He4rt\Vault\Buckets\Bucket;

class BucketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Bucket Name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('share_token')
                    ->label('Share Token')
                    ->placeholder('No share token generated yet')
                    ->disabled()
                    ->dehydrated(false)
                    ->helperText(fn (?Bucket $record): string => $record?->isShared()
                        ? 'This bucket is shared publicly via this token.'
                        : 'Generate a share token to allow public access.'
                    ),
            ]);
    }
}
