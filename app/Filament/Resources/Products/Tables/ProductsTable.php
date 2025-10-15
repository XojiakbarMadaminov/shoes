<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\Stock;
use App\Models\Product;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Illuminate\Support\Collection;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Select;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\ForceDeleteBulkAction;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        // Foydalanuvchi joriy do‘koniga tegishli omborlar
        $stocks = cache()->remember(
            'active_stocks_for_store_' . auth()->id(),
            60,
            fn () => Stock::query()
                ->scopes('active')
                ->whereHas('stores', fn ($q) => $q->where('stores.id', auth()->user()->current_store_id))
                ->get()
        );

        return $table
            ->defaultSort('created_at', 'desc')
            ->columns(array_merge(
                [
                    TextColumn::make('name')
                        ->label('Nomi')
                        ->searchable()
                        ->sortable(),

                    TextColumn::make('barcode')
                        ->label('Bar kod')
                        ->searchable(),

                    TextColumn::make('color')
                        ->label('Rang')
                        ->toggleable(isToggledHiddenByDefault: true),

                    TextColumn::make('initial_price')
                        ->label('Kelgan narxi')
                        ->numeric(),

                    TextColumn::make('price')
                        ->label('Sotish narxi')
                        ->numeric(),

                    TextColumn::make('category.name')
                        ->label('Kategoriyasi')
                        ->sortable()
                        ->searchable(),
                ],

                // 🔹 Dinamik ravishda har bir ombor uchun ustun yaratamiz
                $stocks->map(function ($stock) {
                    return TextColumn::make("stock_{$stock->id}")
                        ->label($stock->name)
                        ->alignCenter()
                        ->getStateUsing(function (Product $record) use ($stock) {
                            // Har bir product uchun ombor bo‘yicha jami miqdor hisoblanadi
                            return $record->sizes()
                                ->with(['stocks' => fn ($q) => $q->where('stock_id', $stock->id)])
                                ->get()
                                ->flatMap(fn ($size) => $size->stocks)
                                ->sum('quantity') ?? 0;
                        });
                })->all()
            ))
            ->recordActions([
                Action::make('print_barcode')
                    ->label('Print Barcode')
                    ->icon('heroicon-o-printer')
                    ->schema([
                        Select::make('size')
                            ->label('Label razmeri')
                            ->options([
                                '30x20' => '3.0 cm x 2.0 cm',
                                '57x30' => '5.7 cm x 3.0 cm',
                            ])
                            ->required(),
                    ])
                    ->action(function (array $data, Product $record) {
                        return redirect()->away(route('product.barcode.pdf', [
                            'product' => $record->id,
                            'size'    => $data['size'],
                        ]));
                    }),

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
                        ->schema([
                            Select::make('size')
                                ->label('Label razmeri')
                                ->options([
                                    '30x20' => '3.0 cm x 2.0 cm',
                                    '57x30' => '5.7 cm x 3.0 cm',
                                ])
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $ids = $records->pluck('id')->toArray();

                            return redirect()->away(route('product.barcodes.bulk', [
                                'ids'  => implode(',', $ids),
                                'size' => $data['size'],
                            ]));
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->modifyQueryUsing(fn ($query) => $query->with(['category', 'sizes.stocks']));
    }
}
