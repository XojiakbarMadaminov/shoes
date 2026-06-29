<?php

namespace App\Filament\Resources\Discounts\Schemas;

use App\Enums\DiscountType;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class DiscountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Chegirma')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->label('Nomi')
                            ->required()
                            ->readOnly()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Select::make('type')
                            ->label('Turi')
                            ->options(DiscountType::options())
                            ->default(DiscountType::GlobalPercent->value)
                            ->required()
                            ->disabled()
                            ->dehydrated()
                            ->reactive()
                            ->afterStateUpdated(function (mixed $state, Set $set): void {
                                $type = self::typeValue($state);

                                if ($type !== DiscountType::SelectedProductsPercent->value) {
                                    $set('products', []);
                                }

                                if ($type !== DiscountType::CategoryPercent->value) {
                                    $set('categories', []);
                                }

                                if ($type !== DiscountType::OrderAmountPercent->value) {
                                    $set('min_order_amount', null);
                                }
                            }),

                        TextInput::make('percent')
                            ->label('Foiz')
                            ->numeric()
                            ->minValue(0.01)
                            ->maxValue(100)
                            ->suffix('%')
                            ->required(),

                        Toggle::make('is_active')
                            ->label('Aktiv')
                            ->default(true),
                    ])
                    ->columns(3),

                Section::make('Shartlar')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('min_order_amount')
                            ->label('Minimal buyurtma summasi')
                            ->numeric()
                            ->minValue(0)
                            ->required(fn (Get $get): bool => self::typeValue($get('type')) === DiscountType::OrderAmountPercent->value)
                            ->visible(fn (Get $get): bool => self::typeValue($get('type')) === DiscountType::OrderAmountPercent->value)
                            ->dehydrated(fn (Get $get): bool => self::typeValue($get('type')) === DiscountType::OrderAmountPercent->value),

                        Select::make('products')
                            ->label('Tanlangan tovarlar')
                            ->relationship('products', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->visible(fn (Get $get): bool => self::typeValue($get('type')) === DiscountType::SelectedProductsPercent->value),

                        Select::make('categories')
                            ->label('Tanlangan kategoriyalar')
                            ->relationship('categories', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->visible(fn (Get $get): bool => self::typeValue($get('type')) === DiscountType::CategoryPercent->value),

                        DateTimePicker::make('starts_at')
                            ->label('Boshlanish vaqti')
                            ->nullable()
                            ->rules(['nullable', 'date']),

                        DateTimePicker::make('ends_at')
                            ->label('Tugash vaqti')
                            ->nullable()
                            ->rules(['nullable', 'date', 'after_or_equal:starts_at']),
                    ])
                    ->columns(2),
            ]);
    }

    private static function typeValue(mixed $state): ?string
    {
        if ($state instanceof DiscountType) {
            return $state->value;
        }

        return is_string($state) ? $state : null;
    }
}
