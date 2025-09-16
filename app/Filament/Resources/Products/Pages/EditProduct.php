<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;
    protected $stocks;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['stocks'])) {
            $this->stocks = $data['stocks'];
            unset($data['stocks']);
        }
        return $data;
    }

    protected function afterSave(): void
    {
        if (!empty($this->stocks)) {
            foreach ($this->stocks as $stockId => $stockData) {
                $this->record->productStocks()->updateOrCreate(
                    ['stock_id' => $stockId],
                    ['quantity' => $stockData['quantity'] ?? 0]
                );
            }
        }
    }
}
