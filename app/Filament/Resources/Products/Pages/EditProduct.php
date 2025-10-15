<?php

namespace App\Filament\Resources\Products\Pages;

use App\Models\Stock;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\Products\ProductResource;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;
    protected $stocks;
    protected $sizesData;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->sizesData = $data['sizes'] ?? [];
        unset($data['sizes']);

        return $data;
    }

    protected function afterSave(): void
    {
        $product         = $this->record;
        $currentSizeRows = collect($this->sizesData);

        // 1️⃣ Hozir formda mavjud razmerlar (36–41 ichidan qolganlar)
        $currentSizes = $currentSizeRows->pluck('size')->toArray();

        // 2️⃣ Bazada mavjud eski razmerlar
        $existingSizes = $product->sizes()->pluck('size')->toArray();

        // 3️⃣ O‘chirilgan razmerlarni aniqlaymiz
        $deletedSizes = array_diff($existingSizes, $currentSizes);

        // 4️⃣ Eski (formda yo‘q) razmerlarni o‘chiramiz
        if (!empty($deletedSizes)) {
            $product->sizes()
                ->whereIn('size', $deletedSizes)
                ->each(function ($size) {
                    $size->stocks()->delete();
                    $size->delete();
                });
        }

        // 5️⃣ Endi mavjud yoki yangi razmerlarni yangilaymiz / yaratamiz
        foreach ($this->sizesData as $sizeRow) {
            $sizeModel = $product->sizes()->updateOrCreate(
                ['size' => $sizeRow['size']],
                []
            );

            // Har bir stock inputini qayta yozamiz
            foreach ($sizeRow as $key => $value) {
                if (str_starts_with($key, 'stock_')) {
                    $stockId = (int) str_replace('stock_', '', $key);
                    $sizeModel->stocks()->updateOrCreate(
                        ['stock_id' => $stockId],
                        ['quantity' => (int) $value]
                    );
                }
            }
        }
    }
}
