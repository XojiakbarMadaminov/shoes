<?php

namespace App\Filament\Resources\Debtors\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;

class DebtorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()->schema([
                    TextInput::make('phone')
                        ->label('Telefon raqam')
                        ->maxLength(9)
                        ->prefix('+998')
                        ->placeholder('90 123 45 67')
                        ->required()
                        ->reactive()
                        ->rule('regex:/^[0-9]{0,9}$/')
                        ->dehydrateStateUsing(fn ($state) => '+998' . preg_replace('/[^0-9]/', '', (string) $state))
                        ->formatStateUsing(fn ($state) => $state ? ltrim(preg_replace('/^\+998/', '', (string) $state), '0') : $state),

                    TextInput::make('full_name')
                        ->label('To`liq ism')
                        ->placeholder('Masalan: Ali Valiyev')
                        ->required(),

                    TextInput::make('amount')
                        ->label('Qarz summasi')
                        ->numeric()
                        ->placeholder('Masalan: 150 000')
                        ->required(),

                    DatePicker::make('date')
                        ->label('Qarz sanasi')
                        ->format('Y-m-d')
                        ->default(today())
                        ->required(),
                ])->columnSpanFull(),

                Textarea::make('note')
                    ->label('Qo`shimcha qaydlar')
                    ->placeholder('Masalan: Do`kon tovarlari uchun...')
                    ->rows(3)
                    ->maxLength(500)
                    ->columnSpanFull(),
            ]);
    }
}
