<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\ProductStock;
use App\Models\StockTransfer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockTransferService
{
    /** @param array<string, mixed> $data */
    public function transfer(array $data): StockTransfer
    {
        $mode        = $data['movement_mode'] ?? StockTransfer::MODE_SELECTED;
        $fromStoreId = (int) ($data['from_store_id'] ?? 0);
        $toStoreId   = (int) ($data['to_store_id'] ?? 0);
        $fromStockId = (int) ($data['from_stock_id'] ?? 0);
        $toStockId   = (int) ($data['to_stock_id'] ?? 0);

        $this->validateLocations($mode, $fromStoreId, $toStoreId, $fromStockId, $toStockId);

        return DB::transaction(function () use ($data, $mode, $fromStoreId, $toStoreId, $fromStockId, $toStockId): StockTransfer {
            $stockRows = $mode === StockTransfer::MODE_ALL
                ? $this->allStockRows($fromStoreId, $fromStockId)
                : $this->selectedStockRows($data['products'] ?? [], $fromStoreId, $fromStockId);

            if ($stockRows->isEmpty()) {
                throw ValidationException::withMessages([
                    'products' => 'Ko‘chirish uchun qoldiq topilmadi.',
                ]);
            }

            $transfer = StockTransfer::query()->create([
                'from_store_id' => $fromStoreId,
                'to_store_id'   => $toStoreId,
                'from_stock_id' => $fromStockId,
                'to_stock_id'   => $toStockId,
                'mode'          => $mode,
                'created_by'    => auth()->id(),
            ]);

            $destinationProducts = [];
            $totalQuantity       = 0;

            foreach ($stockRows as $movement) {
                /** @var ProductStock $sourceStock */
                $sourceStock = $movement['stock'];
                $quantity    = $movement['quantity'];
                $product     = $this->sourceProduct($sourceStock);

                if (!array_key_exists($product->id, $destinationProducts)) {
                    $destinationProducts[$product->id] = $this->destinationProduct($product, $toStoreId);
                }

                /** @var Product $destinationProduct */
                $destinationProduct = $destinationProducts[$product->id];
                $sourceSize         = $sourceStock->product_size_id
                    ? ProductSize::query()->findOrFail($sourceStock->product_size_id)
                    : null;
                $destinationSize = $sourceSize
                    ? $this->destinationSize($destinationProduct, $sourceSize)
                    : null;

                $destinationStock = $this->destinationStock(
                    $destinationProduct,
                    $destinationSize,
                    $toStockId,
                );

                $sourceStock->decrement('quantity', $quantity);
                $destinationStock->increment('quantity', $quantity);

                $transfer->items()->create([
                    'from_product_id'      => $product->id,
                    'to_product_id'        => $destinationProduct->id,
                    'from_product_size_id' => $sourceSize?->id,
                    'to_product_size_id'   => $destinationSize?->id,
                    'product_name'         => $product->name,
                    'barcode'              => $product->barcode,
                    'variant'              => $sourceSize?->size,
                    'quantity'             => $quantity,
                ]);

                $totalQuantity += $quantity;
            }

            $transfer->update(['total_quantity' => $totalQuantity]);

            return $transfer->load('items');
        }, 3);
    }

    private function validateLocations(
        string $mode,
        int $fromStoreId,
        int $toStoreId,
        int $fromStockId,
        int $toStockId,
    ): void {
        if (!in_array($mode, [StockTransfer::MODE_SELECTED, StockTransfer::MODE_ALL], true)) {
            throw ValidationException::withMessages(['movement_mode' => 'Ko‘chirish turi noto‘g‘ri.']);
        }

        if (!$fromStoreId || !$toStoreId || !$fromStockId || !$toStockId) {
            throw ValidationException::withMessages(['locations' => 'Filial va omborlarni to‘liq tanlang.']);
        }

        if ($fromStoreId === $toStoreId) {
            throw ValidationException::withMessages(['to_store_id' => 'Qabul qiluvchi filial boshqa bo‘lishi kerak.']);
        }

        if ($fromStockId === $toStockId) {
            throw ValidationException::withMessages(['to_stock_id' => 'Qabul qiluvchi ombor boshqa bo‘lishi kerak.']);
        }

        $fromStockIsValid = Stock::withoutGlobalScope('current_store')
            ->whereKey($fromStockId)
            ->whereHas('stores', fn ($query) => $query->whereKey($fromStoreId))
            ->exists();
        $toStockIsValid = Stock::withoutGlobalScope('current_store')
            ->whereKey($toStockId)
            ->whereHas('stores', fn ($query) => $query->whereKey($toStoreId))
            ->exists();

        if (!$fromStockIsValid || !$toStockIsValid) {
            throw ValidationException::withMessages(['locations' => 'Tanlangan ombor tegishli filialga biriktirilmagan.']);
        }
    }

    /** @return Collection<int, array{stock: ProductStock, quantity: int}> */
    private function allStockRows(int $fromStoreId, int $fromStockId): Collection
    {
        return ProductStock::withoutGlobalScope('current_store')
            ->where('stock_id', $fromStockId)
            ->where('quantity', '>', 0)
            ->where(function ($query) use ($fromStoreId): void {
                $query->whereHas('product', fn ($productQuery) => $productQuery
                    ->withoutGlobalScope('current_store')
                    ->where('store_id', $fromStoreId))
                    ->orWhereHas('productSize.product', fn ($productQuery) => $productQuery
                        ->withoutGlobalScope('current_store')
                        ->where('store_id', $fromStoreId));
            })
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->map(fn (ProductStock $stock): array => [
                'stock'    => $stock,
                'quantity' => (int) $stock->quantity,
            ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $products
     * @return Collection<int, array{stock: ProductStock, quantity: int}>
     */
    private function selectedStockRows(array $products, int $fromStoreId, int $fromStockId): Collection
    {
        $requested = collect($products)
            ->flatMap(function (array $item): array {
                $productId = (int) ($item['product_id'] ?? 0);
                $type      = $item['type'] ?? null;

                if ($type === Product::TYPE_PACKAGE) {
                    return [[
                        'key'             => "product:{$productId}",
                        'product_id'      => $productId,
                        'product_size_id' => null,
                        'quantity'        => (int) ($item['package_quantity'] ?? 0),
                    ]];
                }

                return collect($item['sizes'] ?? [])->map(fn (array $size): array => [
                    'key'             => 'size:' . (int) ($size['size_id'] ?? 0),
                    'product_id'      => $productId,
                    'product_size_id' => (int) ($size['size_id'] ?? 0),
                    'quantity'        => (int) ($size['quantity'] ?? 0),
                ])->all();
            })
            ->filter(fn (array $row): bool => $row['product_id'] > 0 && $row['quantity'] > 0)
            ->groupBy('key')
            ->map(function (Collection $rows): array {
                $first             = $rows->first();
                $first['quantity'] = $rows->sum('quantity');

                return $first;
            })
            ->sortKeys();

        if ($requested->isEmpty()) {
            throw ValidationException::withMessages(['products' => 'Ko‘chiriladigan miqdorni kiriting.']);
        }

        return $requested->map(function (array $row) use ($fromStoreId, $fromStockId): array {
            $product = Product::withoutGlobalScope('current_store')
                ->whereKey($row['product_id'])
                ->where('store_id', $fromStoreId)
                ->first();

            if (!$product) {
                throw ValidationException::withMessages(['products' => 'Tanlangan mahsulot manba filialga tegishli emas.']);
            }

            $query = ProductStock::withoutGlobalScope('current_store')
                ->where('stock_id', $fromStockId);

            if ($row['product_size_id']) {
                $sizeBelongsToProduct = ProductSize::query()
                    ->whereKey($row['product_size_id'])
                    ->where('product_id', $product->id)
                    ->exists();

                if (!$sizeBelongsToProduct) {
                    throw ValidationException::withMessages(['products' => "{$product->name} varianti noto‘g‘ri."]);
                }

                $query->whereNull('product_id')->where('product_size_id', $row['product_size_id']);
            } else {
                $query->where('product_id', $product->id)->whereNull('product_size_id');
            }

            /** @var ProductStock|null $stock */
            $stock = $query->lockForUpdate()->first();

            if (!$stock || $stock->quantity < $row['quantity']) {
                $available = (int) ($stock?->quantity ?? 0);

                throw ValidationException::withMessages([
                    'products' => "{$product->name} uchun qoldiq yetarli emas. Mavjud: {$available}.",
                ]);
            }

            return ['stock' => $stock, 'quantity' => (int) $row['quantity']];
        })->values();
    }

    private function sourceProduct(ProductStock $stock): Product
    {
        $productId = $stock->product_id ?: ProductSize::query()
            ->whereKey($stock->product_size_id)
            ->value('product_id');

        return Product::withoutGlobalScope('current_store')->findOrFail($productId);
    }

    private function destinationProduct(Product $source, int $toStoreId): Product
    {
        $destination = null;

        if (filled($source->barcode)) {
            $destination = Product::withoutGlobalScope('current_store')
                ->where('store_id', $toStoreId)
                ->where('barcode', $source->barcode)
                ->lockForUpdate()
                ->first();
        }

        if ($destination && $destination->type !== $source->type) {
            throw ValidationException::withMessages([
                'products' => "{$source->barcode} barcode’li mahsulot turi filiallarda bir xil emas.",
            ]);
        }

        if ($destination) {
            return $destination;
        }

        return Product::withoutGlobalScope('current_store')->create([
            'store_id'      => $toStoreId,
            'name'          => $source->name,
            'barcode'       => $source->barcode,
            'initial_price' => $source->initial_price,
            'price'         => $source->price,
            'category_id'   => $source->category_id,
            'type'          => $source->type,
        ]);
    }

    private function destinationSize(Product $destinationProduct, ProductSize $sourceSize): ProductSize
    {
        return ProductSize::query()->firstOrCreate([
            'product_id' => $destinationProduct->id,
            'size'       => $sourceSize->size,
        ]);
    }

    private function destinationStock(
        Product $destinationProduct,
        ?ProductSize $destinationSize,
        int $toStockId,
    ): ProductStock {
        $keys = [
            'stock_id'        => $toStockId,
            'product_id'      => $destinationSize ? null : $destinationProduct->id,
            'product_size_id' => $destinationSize?->id,
        ];

        return ProductStock::withoutGlobalScope('current_store')->firstOrCreate($keys, ['quantity' => 0]);
    }
}
