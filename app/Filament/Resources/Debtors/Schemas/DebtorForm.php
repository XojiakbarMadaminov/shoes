<?php

namespace App\Filament\Resources\Debtors\Schemas;

use App\Models\Client;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
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
                    Select::make('client_id')
                        ->label('Klient')
                        ->searchable()
                        ->preload()
                        ->options(fn () => Client::orderBy('full_name')->pluck('full_name', 'id'))
                        ->getSearchResultsUsing(function (string $search) {
                            return Client::query()
                                ->where('full_name', 'ilike', "%{$search}%")
                                ->orWhere('phone', 'ilike', "%{$search}%")
                                ->limit(50)
                                ->pluck('full_name', 'id');
                        })
                        ->getOptionLabelUsing(fn ($value) => optional(Client::find($value))->full_name)
                        ->required(),

                    TextInput::make('client_phone')
                        ->label('Telefon')
                        ->disabled()
                        ->dehydrated(false)
                        ->formatStateUsing(function ($state, callable $get) {
                            $id = $get('client_id');

                            return ($id ? Client::find($id) : null)?->phone;
                        }),

                    TextInput::make('amount')
                        ->label('Qarz summasi')
                        ->numeric()
                        ->placeholder('Masalan: 150 000')
                        ->required(),

                    DatePicker::make('date')
                        ->label('Qarz sanasi')
                        ->default(today())
                        ->required(),
                ])->columnSpanFull(),

                Textarea::make('note')
                    ->label('Qo‘shimcha qaydlar')
                    ->placeholder('Masalan: Do‘kon tovarlari uchun...')
                    ->rows(3)
                    ->maxLength(500)
                    ->columnSpanFull(),
            ]);
    }
}
