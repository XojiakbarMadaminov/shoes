<?php

namespace App\Filament\Resources\Discounts\Tables;

use Filament\Tables\Table;
use App\Enums\DiscountType;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;

class DiscountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id')
            ->columns([
                TextColumn::make('name')
                    ->label('Nomi')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Turi')
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): ?string => self::typeLabel($state))
                    ->color(fn (mixed $state): string => match (self::type($state)) {
                        DiscountType::GlobalPercent           => 'info',
                        DiscountType::SelectedProductsPercent => 'warning',
                        DiscountType::CategoryPercent         => 'gray',
                        DiscountType::OrderAmountPercent      => 'success',
                        default                               => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('percent')
                    ->label('Foiz')
                    ->formatStateUsing(fn (mixed $state): string => number_format((float) $state, 2, '.', ' ') . '%')
                    ->sortable(),

                TextColumn::make('min_order_amount')
                    ->label('Minimal summa')
                    ->formatStateUsing(fn (mixed $state): string => filled($state) ? number_format((float) $state, 0, '.', ' ') : '-')
                    ->sortable(),

                TextColumn::make('products_count')
                    ->label('Tovarlar')
                    ->counts('products')
                    ->sortable(),

                TextColumn::make('categories_count')
                    ->label('Kategoriyalar')
                    ->counts('categories')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean(),

                TextColumn::make('starts_at')
                    ->label('Boshlanish')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                TextColumn::make('ends_at')
                    ->label('Tugash')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Turi')
                    ->options(DiscountType::options()),

                TernaryFilter::make('is_active')
                    ->label('Aktiv'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }

    private static function type(mixed $state): ?DiscountType
    {
        if ($state instanceof DiscountType) {
            return $state;
        }

        return DiscountType::tryFrom((string) $state);
    }

    private static function typeLabel(mixed $state): ?string
    {
        return self::type($state)?->getLabel();
    }
}
