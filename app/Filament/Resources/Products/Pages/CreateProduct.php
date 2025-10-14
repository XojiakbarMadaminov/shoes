<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\ProductStock;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;
    protected $stocks;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (isset($data['stocks'])) {
            $this->stocks = $data['stocks'];
            unset($data['stocks']);
        }
        return $data;
    }

    protected function afterCreate(): void
    {
        if (!empty($this->stocks)) {
            foreach ($this->stocks as $stockId => $stockData) {
                ProductStock::create([
                    'product_id' => $this->record->id,
                    'stock_id' => $stockId,
                    'quantity' => $stockData['quantity'] ?? 0,
                ]);
            }
        }
    }


}
