<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('User Information')
                    ->schema([
                        TextInput::make('name')
                            ->label('Ism')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->unique(ignoreRecord: true)
                            ->disabledOn('edit')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('password')
                            ->label('Parol')
                            ->password()
                            ->dehydrateStateUsing(fn($state) => !empty($state) ? bcrypt($state) : null)
                            ->dehydrated(fn($state) => filled($state))
                            ->maxLength(255)
                            ->hiddenOn('edit'),
                    ])->columns(2),

                Section::make('Stores')
                    ->schema([
                        Select::make('stores')
                            ->label('Magazinlar')
                            ->multiple()
                            ->relationship('stores', 'name')
                            ->preload()
                            ->searchable(),

                        Select::make('roles')
                            ->label('Rol')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable(),
                    ]),
            ]);
    }
}
