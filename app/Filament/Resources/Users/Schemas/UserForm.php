<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;

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
                            ->disabled(fn (?User $record): bool => $record !== null && auth()->user()?->email !== 'super@gmail.com')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('password')
                            ->label('Parol')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => !empty($state) ? bcrypt($state) : null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->maxLength(255)
                            ->hidden(function (?User $record): bool {
                                if ($record === null) {
                                    return false; // create page: always show
                                }

                                // Super's password: only editable by themselves
                                if ($record->email === 'super@gmail.com') {
                                    return auth()->id() !== $record->id;
                                }

                                // Everyone else: editable by any user
                                return false;
                            }),
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
                            ->searchable()
                            ->disabled(fn (?User $record): bool => $record !== null && $record->email === 'super@gmail.com' && auth()->id() !== $record->id)
                            ->dehydrated(fn (?User $record): bool => !($record !== null && $record->email === 'super@gmail.com' && auth()->id() !== $record->id)),
                    ]),
            ]);
    }
}
