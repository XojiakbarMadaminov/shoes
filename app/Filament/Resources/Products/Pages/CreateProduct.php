<?php

namespace App\Filament\Resources\Products\Pages;

use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\Products\ProductResource;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;
    protected $sizesData;
    protected $packageStockData;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['store_id'] = auth()->user()?->current_store_id;

        $this->sizesData        = $data['sizes'] ?? [];
        $this->packageStockData = collect($data)
            ->filter(fn ($v, $k) => str_starts_with($k, 'pkg_stock_'))
            ->all();

        unset($data['sizes']);
        foreach (array_keys($this->packageStockData) as $k) {
            unset($data[$k]);
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $product = $this->record;

        if (($product->type ?? 'size') === 'size') {
            foreach ($this->sizesData as $sizeRow) {
                $size = $product->sizes()->create(['size' => $sizeRow['size']]);

                foreach ($sizeRow as $key => $val) {
                    if (str_starts_with($key, 'stock_') && $val !== null) {
                        $stockId = (int) str_replace('stock_', '', $key);
                        // Save into unified product_stocks table
                        $size->productStocks()->create([
                            'stock_id' => $stockId,
                            'quantity' => (int) $val,
                        ]);
                    }
                }
            }
        } else {
            // package based: create product-level stock rows
            foreach ($this->packageStockData as $key => $val) {
                if ($val === null) {
                    continue;
                }
                $stockId = (int) str_replace('pkg_stock_', '', $key);
                $product->productStocks()->create([
                    'stock_id' => $stockId,
                    'quantity' => (int) $val,
                ]);
            }
        }
    }
}
