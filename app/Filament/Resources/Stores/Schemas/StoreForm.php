<?php

namespace App\Filament\Resources\Stores\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;

class StoreForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columnSpanFull()
                    ->columns(3)
                    ->components([
                        TextInput::make('name')->label('Nomi')->required(),
                        TextInput::make('address')->label('Manzil')->nullable(),
                        TextInput::make('phone')
                            ->label('Telefon raqam')
                            ->maxLength(9)
                            ->prefix('+998')
                            ->placeholder('90 123 45 67')
                            ->required()
                            ->rule('regex:/^[0-9]{0,9}$/')
                            ->dehydrateStateUsing(fn ($state) => '+998' . preg_replace('/\D/', '', $state))
                            ->formatStateUsing(fn ($state) => $state ? ltrim(preg_replace('/^\+998/', '', $state), '0') : ''),
                        Toggle::make('send_sms')->label('SMS yuborishga ruhsat')->default(true),
                    ]),

            ]);
    }
}
