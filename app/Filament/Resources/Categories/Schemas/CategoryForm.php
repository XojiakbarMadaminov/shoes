<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Tovar kategoriyasi')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')->label('Nomi')->required()->maxLength(255),
                        Toggle::make('is_active')->label('Aktiv')->default(true),
                    ])
            ]);
    }
}
