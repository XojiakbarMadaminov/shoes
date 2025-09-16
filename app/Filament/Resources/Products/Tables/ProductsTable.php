<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\Stock;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        $stocks = cache()->remember(
            'active_stocks_for_store_' . auth()->id(),
            60, // 1 soat
            fn() => Stock::query()
                ->scopes('active')
                ->whereHas('stores', fn($q) => $q->where('stores.id', auth()->user()->current_store_id))
                ->get()
        );

        return $table
            ->defaultSort('created_at', 'desc')
            ->columns(array_merge(
                [
                    TextColumn::make('name')
                        ->label('Nomi')
                        ->searchable(),

                    TextColumn::make('barcode')
                        ->label('Bar kod')
                        ->searchable(),

                    TextColumn::make('initial_price')
                        ->label('Kelgan narxi')
                        ->numeric(),

                    TextColumn::make('price')
                        ->label('Narxi')
                        ->numeric(),
                ],

                $stocks->map(fn($stock) => TextColumn::make("stock_{$stock->id}")
                    ->label($stock->name)
                    ->alignCenter()
                    ->getStateUsing(fn($record) => optional(
                        $record->productStocks
                            ->firstWhere('stock_id', $stock->id)
                    )?->quantity ?? 0
                    )
                )->all()
            ))
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(fn($query) => $query->with('productStocks'));
    }
}
