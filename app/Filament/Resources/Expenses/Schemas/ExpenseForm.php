<?php

namespace App\Filament\Resources\Expenses\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ExpenseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('note')
                    ->label('Izoh')
                    ->columnSpanFull(),
                TextInput::make('amount')
                    ->label('Miqdor')
                    ->required()
                    ->numeric(),
                DateTimePicker::make('date')
                    ->label('Sana')
                    ->required()
                    ->default(now()),
            ]);
    }
}
