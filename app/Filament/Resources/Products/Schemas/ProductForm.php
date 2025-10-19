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
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        // Optimize: fetch active stocks once and reuse in all closures
        $stocks = self::getActiveStocks();

        return $schema
            ->components([
                Section::make('Tovar maʼlumotlari')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->label('Nomi')
                            ->unique()
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

                        Select::make('type')
                            ->label('Turi')
                            ->options([
                                'size'    => 'Razmerli',
                                'package' => 'Paketli',
                            ])
                            ->default('package')
                            ->required()
                            ->reactive(),
                    ])->columns(3),

                Section::make('Rasm')
                    ->columnSpanFull()
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('images')
                            ->collection('images')
                            ->label('Mahsulot rasmlari')
                            ->multiple()
                            ->reorderable()
                            ->conversion('optimized') // WebP konversiyani ishlatadi
                            ->responsiveImages()
                            ->visibility('public'),
                    ]),

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
                    ->visible(fn (Get $get) => ($get('type') ?? 'size') === 'size')
                    ->schema([
                        Repeater::make('sizes')
                            ->label('Razmerlar')
                            ->schema(function () use ($stocks) {
                                return [
                                    Grid::make()
                                        ->columns(count($stocks) + 1)
                                        ->schema(function () use ($stocks) {
                                            $fields = [];

                                            $fields[] = TextInput::make('size')
                                                ->label('Razmer')
                                                ->numeric();

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
                                return collect(range(36, 41))
                                    ->map(fn ($size) => ['size' => $size])
                                    ->toArray();
                            })
                            ->columns(1)
                            ->reorderable(false),

                    ]),

                Section::make('Paket miqdori')
                    ->columnSpanFull()
                    ->description('Har bir ombor uchun umumiy paket miqdorini kiriting')
                    ->visible(fn (Get $get) => ($get('type') ?? 'size') === 'package')
                    ->schema(function () use ($stocks) {
                        return [
                            Grid::make()
                                ->columns(count($stocks))
                                ->schema(function () use ($stocks) {
                                    $fields = [];
                                    foreach ($stocks as $id => $name) {
                                        $fields[] = TextInput::make("pkg_stock_{$id}")
                                            ->label($name)
                                            ->numeric()
                                            ->default(0);
                                    }

                                    return $fields;
                                }),
                        ];
                    }),
            ]);
    }

    private static function getActiveStocks(): array
    {
        static $cached = null;
        if ($cached === null) {
            $cached = Stock::scopes('active')->pluck('name', 'id')->toArray();
        }

        return $cached;
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
