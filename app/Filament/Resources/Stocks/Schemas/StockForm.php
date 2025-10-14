<?php

namespace App\Filament\Resources\Stocks\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StockForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
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

                        Toggle::make('is_main')
                            ->label('Asosiy savdo bo\'ladigan sklad')
                            ->default(true),

                        Toggle::make('is_active')
                            ->label('Status')
                            ->default(true),
                    ])
                    ->columns(2)
                    ->columnSpanFull()
            ]);
    }
}
