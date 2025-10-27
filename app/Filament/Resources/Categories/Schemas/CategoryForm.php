<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;

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
                        SpatieMediaLibraryFileUpload::make('image')
                            ->collection('image')
                            ->image()
                            ->visible('public')
                            ->required(),
                    ]),
            ]);
    }
}
