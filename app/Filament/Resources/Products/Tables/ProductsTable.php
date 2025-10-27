<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\Stock;
use App\Models\Product;
use Filament\Tables\Table;
use App\Models\ProductStock;
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
use Filament\Tables\Columns\ImageColumn;
use Filament\Actions\ForceDeleteBulkAction;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        $stocks = cache()->remember(
            'active_stocks_for_store_' . auth()->id(),
            3600,
            fn () => Stock::query()
                ->scopes('active')
                ->whereHas('stores', fn ($q) => $q->where('stores.id', auth()->user()->current_store_id))
                ->get()
        );

        return $table
            ->defaultSort('created_at', 'desc')
            ->columns(array_merge(
                [
                    TextColumn::make('id')->label('ID'),
                    ImageColumn::make('first_image')
                        ->label('Rasm')
                        ->getStateUsing(fn (Product $record) => $record->getPrimaryImageUrl())
                        ->square()
                        ->height(32)
                        ->width(32)
                        ->extraAttributes(['class' => 'cursor-zoom-in'])
                        ->action(
                            Action::make('zoom_image')
                                ->label("Ko'rish")
                                ->modalSubmitAction(false)
                                ->modalHeading(fn (Product $record) => $record->name)
                                ->modalWidth('5xl')
                                ->modalContent(fn (Product $record) => view('filament.products.partials.image-zoom', [
                                    'urls' => $record->getImageUrls(),
                                ]))
                        ),
                    TextColumn::make('name')->label('Nomi')->searchable()->sortable(),
                    TextColumn::make('barcode')->label('Bar kod')->searchable(),
                    TextColumn::make('initial_price')->label('Kelgan narxi')->numeric(),
                    TextColumn::make('price')->label('Sotish narxi')->numeric(),
                    TextColumn::make('category.name')->label('Kategoriyasi')->sortable()->searchable(),
                    TextColumn::make('type')->label('Turi')->badge()->formatStateUsing(fn ($state) => $state === 'package' ? 'Paket' : 'Razmer'),
                ],
                $stocks->map(function ($stock) {
                    return TextColumn::make("stock_{$stock->id}_qty")
                        ->label($stock->name)
                        ->alignCenter()
                        ->formatStateUsing(fn ($state) => (int) ($state ?? 0))
                        ->default(0)
                        ->disabled(fn (Product $record) => ($record->type ?? 'size') === 'package');
                })->all()
            ))
            ->recordActions([
                Action::make('sizes_breakdown')
                    ->label('Razmerlar')
                    ->icon('heroicon-o-queue-list')
                    ->visible(fn (Product $record) => ($record->type ?? 'size') === 'size')
                    ->modalHeading(fn (Product $record) => "{$record->name} — Razmerlar bo‘yicha zaxira")
                    ->modalSubmitAction(false)
                    ->modalWidth('4xl')
                    ->modalContent(function (Product $record) {
                        $product = $record->loadMissing('sizes');
                        $stocks  = Stock::scopes('active')->get();
                        $sizes   = $product->sizes()->orderBy('size')->get();

                        $data = [];
                        foreach ($stocks as $stock) {
                            $row = [];
                            foreach ($sizes as $size) {
                                $qty = ProductStock::where('stock_id', $stock->id)
                                    ->where('product_size_id', $size->id)
                                    ->value('quantity') ?? 0;
                                $row[$size->size] = (int) $qty;
                            }
                            $data[] = [
                                'stock' => $stock->name,
                                'sizes' => $row,
                                'total' => array_sum($row),
                            ];
                        }

                        return view('filament.products.partials.sizes-breakdown', [
                            'product' => $product,
                            'sizes'   => $sizes->pluck('size')->values()->all(),
                            'rows'    => $data,
                        ]);
                    }),
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
            ->modifyQueryUsing(function ($query) use ($stocks) {
                $query->with(['category', 'sizes:id,product_id']);

                foreach ($stocks as $stock) {
                    $alias = "stock_{$stock->id}_qty";

                    $query->addSelect([
                        $alias => ProductStock::query()
                            ->selectRaw('COALESCE(SUM(product_stocks.quantity), 0)')
                            ->leftJoin('product_sizes', 'product_sizes.id', '=', 'product_stocks.product_size_id')
                            ->where('product_stocks.stock_id', $stock->id)
                            ->where(function ($subQuery) {
                                $subQuery
                                    ->whereColumn('product_stocks.product_id', 'products.id')
                                    ->orWhereColumn('product_sizes.product_id', 'products.id');
                            }),
                    ]);
                }
            });
    }
}
