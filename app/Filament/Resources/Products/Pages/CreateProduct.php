<?php

namespace App\Filament\Resources\Products\Pages;

use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\Products\ProductResource;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;
    protected $sizesData;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->sizesData = $data['sizes'] ?? [];
        unset($data['sizes']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $product = $this->record;

        foreach ($this->sizesData as $sizeRow) {
            $size = $product->sizes()->create(['size' => $sizeRow['size']]);

            foreach ($sizeRow as $key => $val) {
                if (str_starts_with($key, 'stock_') && $val !== null) {
                    $stockId = (int) str_replace('stock_', '', $key);
                    $size->stocks()->create([
                        'stock_id' => $stockId,
                        'quantity' => (int) $val,
                    ]);
                }
            }
        }
    }
}
