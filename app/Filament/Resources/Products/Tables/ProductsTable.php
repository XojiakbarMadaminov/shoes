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
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
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

                    TextColumn::make('yuan_price')
                        ->label('Yuan narxi (¥)')
                        ->numeric(),

                    TextColumn::make('initial_price')
                        ->label('Kelgan narxi')
                        ->numeric(),

                    TextColumn::make('price')
                        ->label('Narxi')
                        ->numeric(),
                    TextColumn::make('category.name')
                        ->label('Kategoriyasi')
                        ->sortable()
                        ->searchable(),
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
                SelectFilter::make('category_id')
                    ->label('Kategoriyasi')
                    ->relationship('category', 'name', fn($query) => $query->scopes('active')),
                Filter::make('stock_quantity')
                    ->label('Ombordagi miqdor')
                    ->schema([
                        Select::make('stock_id')
                            ->label('Ombor')
                            ->options(
                                Stock::query()
                                    ->scopes('active')
                                    ->pluck('name', 'id')
                                    ->toArray()
                            )
                            ->required(),

                        TextInput::make('quantity')
                            ->label('Miqdor')
                            ->numeric()
                            ->default(0)
                            ->required(),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['stock_id']) || empty($data['quantity'])) {
                            return $query;
                        }

                        return $query->whereHas('productStocks', function ($q) use ($data) {
                            $q->where('stock_id', $data['stock_id'])
                                ->where('quantity', '<=', (int)$data['quantity']);
                        });
                    }),
            ])
            ->recordActions([
                Action::make('print_barcode')
                    ->label('Print Barcode')
                    ->icon('heroicon-o-printer')
                    ->schema([
                        Select::make('size')
                            ->label('Label Razmeri')
                            ->options([
                                '30x20' => '3.0 cm x 2.0 cm',
                                '57x30' => '5.7 cm x 3.0 cm',
//                                '85x65' => '8.5 cm x 6.5 cm',
                            ])
                            ->required(),
                    ])
                    ->action(function (array $data, Product $record) {
                        return redirect()->away(route('product.barcode.pdf', [
                            'product' => $record->id,
                            'size' => $data['size'],
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
//                                    '85x65' => '8.5 cm x 6.5 cm',
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
                        ->deselectRecordsAfterCompletion()

                ]),
            ])
            ->modifyQueryUsing(fn($query) => $query->with('productStocks'));
    }
}
