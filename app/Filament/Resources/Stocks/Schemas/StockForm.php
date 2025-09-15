<?php

namespace App\Filament\Resources\Stocks\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class StockForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nomi')
                    ->required(),

                Select::make('stores')
                    ->label('Magazinlar')
                    ->multiple()
                    ->relationship('stores', 'name')
                    ->preload()
                    ->searchable()
                    ->required(),

                Toggle::make('is_active')
                    ->label('Status')
                    ->default(true),
            ]);
    }
}
