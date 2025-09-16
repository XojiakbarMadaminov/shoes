<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Stock;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('barcode')
                    ->label('Bar kod')
                    ->unique('products', 'barcode', ignoreRecord: true)
                    ->numeric()
                    ->required()
                    ->autofocus()
                    ->suffixAction(
                        Action::make('generateBarcode')
                            ->icon('heroicon-m-sparkles')
                            ->tooltip('EAN-13 Bar kod yaratish')
                            ->action(function (Set $set) {
                                $set('barcode', self::generateEAN13Barcode());
                            })
                    ),
                TextInput::make('name')->label('Nomi')->required(),
                TextInput::make('initial_price')->label('Kelgan narxi')->numeric(),
                TextInput::make('price')
                    ->label('Sotish narxi')
                    ->numeric()
                    ->required()
                    ->rule(function (callable $get) {
                        $initial = $get('initial_price');

                        return function (string $attribute, $value, $fail) use ($initial) {
                            if ($initial !== null && $value <= $initial) {
                                $fail('Sotish narxi kelgan narxidan katta bo‘lishi kerak.');
                            }
                        };
                    }),
                Section::make('Tovar miqdori')
                    ->columnSpanFull()
                    ->schema(function ($record) {
                        $user = auth()->user();

                        $stocks = Stock::query()
                            ->where('is_active', true)
                            ->whereHas('stores', fn($q) => $q->where('stores.id', $user->current_store_id))
                            ->get();

                        return [
                            Grid::make($stocks->count())
                            ->schema(
                                $stocks->map(fn($stock) =>
                                TextInput::make("stocks.{$stock->id}.quantity")
                                    ->label($stock->name)
                                    ->numeric()
                                    ->afterStateHydrated(function (TextInput $component) use ($record, $stock) {
                                        if ($record) {
                                            $ps = $record->productStocks()
                                                ->where('stock_id', $stock->id)
                                                ->first();
                                            $component->state($ps?->quantity ?? 0);
                                        }
                                    })
                                )->toArray()
                            ),
                        ];
                    })
            ]);
    }


    private static function generateEAN13Barcode(): string
    {
        $code = '';
        for ($i = 0; $i < 12; $i++) {
            $code .= random_int(0, 9);
        }

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = (int) $code[$i];
            $sum += ($i % 2 === 0) ? $digit : $digit * 3;
        }

        $checksum = (10 - ($sum % 10)) % 10;

        return $code . $checksum;
    }
}
