<?php

declare(strict_types=1);

namespace He4rt\Admin\Filament\Resources\BlockedIps\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BlockedIpForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('ip')
                    ->label('IP Address')
                    ->required()
                    ->maxLength(45)
                    ->unique(ignoreRecord: true),

                Textarea::make('reason')
                    ->label('Reason')
                    ->required()
                    ->maxLength(1000)
                    ->rows(3),

                DateTimePicker::make('blocked_until')
                    ->label('Blocked Until')
                    ->nullable()
                    ->helperText('Leave empty for a permanent block.'),
            ]);
    }
}
