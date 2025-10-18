<?php

namespace App\Filament\Resources\Products\Pages;

use App\Models\Stock;
use App\Models\ProductStock;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\Products\ProductResource;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),

        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $product = $this->record;
        $stocks  = Stock::all();

        $data['sizes'] = $product->sizes()
            ->with('productStocks')
            ->get()
            ->map(function ($size) use ($stocks) {
                $row = ['size' => $size->size];
                foreach ($stocks as $stock) {
                    $row["stock_{$stock->id}"] = $size->productStocks
                        ->firstWhere('stock_id', $stock->id)?->quantity ?? 0;
                }

                return $row;
            })
            ->values()
            ->toArray();

        if (($product->type ?? 'size') === 'package') {
            foreach ($stocks as $stock) {
                $data["pkg_stock_{$stock->id}"] = (int) (ProductStock::whereNull('product_size_id')
                    ->where('product_id', $product->id)
                    ->where('stock_id', $stock->id)
                    ->value('quantity') ?? 0);
            }
        }

        return $data;
    }
}
