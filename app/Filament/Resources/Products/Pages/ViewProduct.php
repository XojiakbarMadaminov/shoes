<?php

namespace App\Filament\Resources\Products\Pages;

use App\Models\Stock;
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
            ->with('stocks')
            ->get()
            ->map(function ($size) use ($stocks) {
                $row = ['size' => $size->size];
                foreach ($stocks as $stock) {
                    $row["stock_{$stock->id}"] = $size->stocks
                        ->firstWhere('stock_id', $stock->id)?->quantity ?? 0;
                }

                return $row;
            })
            ->values()
            ->toArray();

        return $data;
    }
}
