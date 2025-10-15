<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Stock;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Tovar maʼlumotlari')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->label('Nomi')
                            ->required()
                            ->columnSpanFull(),

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

                        Select::make('category_id')
                            ->label('Kategoriyasi')
                            ->preload()
                            ->relationship('category', 'name')
                            ->searchable(),

                        Select::make('color_id')->relationship('color', 'title')->label('Rang'),
                    ])->columns(3),

                Section::make('Narxlar')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('initial_price')
                            ->label('Kelgan narxi')
                            ->numeric()
                            ->required(),

                        TextInput::make('price')
                            ->label('Sotish narxi')
                            ->numeric()
                            ->required(),
                    ])->columns(),

                Section::make('Razmerlar va Stocklar')
                    ->columnSpanFull()
                    ->description('Har bir razmer uchun har bir ombordagi miqdorni kiriting')
                    ->schema([
                        Repeater::make('sizes')
                            ->label('Razmerlar')
                            ->schema(function () {
                                $stocks = Stock::where('is_active', true)->pluck('name', 'id')->toArray();

                                return [
                                    Grid::make()
                                        ->columns(count($stocks) + 1) // 1 ta razmer + har bir stock uchun 1 ta input
                                        ->schema(function () use ($stocks) {
                                            $fields = [];

                                            // 1-ustun: Razmer
                                            $fields[] = TextInput::make('size')
                                                ->label('Razmer')
                                                ->numeric();

                                            // Keyingi ustunlar: Har bir ombor uchun input
                                            foreach ($stocks as $id => $name) {
                                                $fields[] = TextInput::make("stock_{$id}")
                                                    ->label($name)
                                                    ->numeric()
                                                    ->default(0);
                                            }

                                            return $fields;
                                        }),
                                ];
                            })
                            ->default(function () {
                                // Form ochilganda default 36–41 razmerlar chiqadi
                                return collect(range(36, 41))
                                    ->map(fn ($size) => ['size' => $size])
                                    ->toArray();
                            })
                            ->columns(1)
                            ->reorderable(false),   // tartibini o‘zgartirishni o‘chiradi

                    ]),
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
