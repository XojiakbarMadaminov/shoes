<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\Product;
use App\Models\Stock;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

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
                Action::make('print_barcode')
                    ->label('Print Barcode')
                    ->icon('heroicon-o-printer')
                    ->url(fn (Product $record) => route('product.barcode.pdf', $record))
                    ->openUrlInNewTab(),
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    BulkAction::make('bulk_print_barcode')
                        ->label('Barcodeni chop etish')
                        ->icon('heroicon-o-printer')
                        ->action(function (Collection $records) {
                            $ids = $records->pluck('id')->toArray();
                            $url = route('product.barcodes.bulk', ['ids' => $ids]);

                            // Redirect qilish uchun response qaytaramiz:
                            return redirect($url);
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                ]),
            ])
            ->modifyQueryUsing(fn($query) => $query->with('productStocks'));
    }
}
