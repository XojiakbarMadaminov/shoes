<?php

use App\Models\User;
use App\Models\Stock;
use App\Models\Store;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\ProductStock;
use App\Models\StockTransfer;
use App\Services\StockTransferService;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('moves every product and treats different barcodes as different products', function () {
    [$fromStore, $fromStock] = createTransferLocation('Chilonzor');
    [$toStore, $toStock]     = createTransferLocation('Yunusobod');
    $user                    = User::factory()->create(['current_store_id' => $fromStore->id]);

    $firstSource  = createTransferPackageProduct($fromStore, $fromStock, 'Bir xil nom', '100001', 5);
    $secondSource = createTransferPackageProduct($fromStore, $fromStock, 'Bir xil nom', '100002', 7);
    $existing     = createTransferPackageProduct($toStore, $toStock, 'Bir xil nom', '100001', 2);

    $this->actingAs($user);

    $transfer = app(StockTransferService::class)->transfer([
        'movement_mode' => StockTransfer::MODE_ALL,
        'from_store_id' => $fromStore->id,
        'to_store_id'   => $toStore->id,
        'from_stock_id' => $fromStock->id,
        'to_stock_id'   => $toStock->id,
    ]);

    $newDestination = Product::withoutGlobalScope('current_store')
        ->where('store_id', $toStore->id)
        ->where('barcode', '100002')
        ->sole();

    expect($transfer->mode)->toBe(StockTransfer::MODE_ALL)
        ->and($transfer->total_quantity)->toBe(12)
        ->and($transfer->items)->toHaveCount(2)
        ->and(ProductStock::withoutGlobalScope('current_store')->where('product_id', $firstSource->id)->value('quantity'))->toBe(0)
        ->and(ProductStock::withoutGlobalScope('current_store')->where('product_id', $secondSource->id)->value('quantity'))->toBe(0)
        ->and(ProductStock::withoutGlobalScope('current_store')->where('product_id', $existing->id)->value('quantity'))->toBe(7)
        ->and($newDestination->name)->toBe('Bir xil nom')
        ->and(ProductStock::withoutGlobalScope('current_store')->where('product_id', $newDestination->id)->value('quantity'))->toBe(7)
        ->and(Product::withoutGlobalScope('current_store')->where('store_id', $toStore->id)->where('name', 'Bir xil nom')->count())->toBe(2);
});

it('moves only selected quantities and maps variants by their value', function () {
    [$fromStore, $fromStock] = createTransferLocation('Olmazor');
    [$toStore, $toStock]     = createTransferLocation('Sergeli');
    $user                    = User::factory()->create(['current_store_id' => $fromStore->id]);
    $product                 = Product::withoutGlobalScope('current_store')->create([
        'store_id'      => $fromStore->id,
        'name'          => 'Futbolka',
        'barcode'       => '200001',
        'type'          => Product::TYPE_SIZE,
        'initial_price' => 50_000,
        'price'         => 80_000,
    ]);
    $small = ProductSize::query()->create(['product_id' => $product->id, 'size' => 'S']);
    $large = ProductSize::query()->create(['product_id' => $product->id, 'size' => 'L']);
    ProductStock::withoutGlobalScope('current_store')->create([
        'product_size_id' => $small->id,
        'stock_id'        => $fromStock->id,
        'quantity'        => 8,
    ]);
    ProductStock::withoutGlobalScope('current_store')->create([
        'product_size_id' => $large->id,
        'stock_id'        => $fromStock->id,
        'quantity'        => 4,
    ]);

    $this->actingAs($user);

    $transfer = app(StockTransferService::class)->transfer([
        'movement_mode' => StockTransfer::MODE_SELECTED,
        'from_store_id' => $fromStore->id,
        'to_store_id'   => $toStore->id,
        'from_stock_id' => $fromStock->id,
        'to_stock_id'   => $toStock->id,
        'products'      => [[
            'product_id' => $product->id,
            'type'       => Product::TYPE_SIZE,
            'sizes'      => [
                ['size_id' => $small->id, 'quantity' => 3],
                ['size_id' => $large->id, 'quantity' => 0],
            ],
        ]],
    ]);

    $destinationProduct = Product::withoutGlobalScope('current_store')
        ->where('store_id', $toStore->id)
        ->where('barcode', '200001')
        ->sole();
    $destinationSize = ProductSize::query()
        ->where('product_id', $destinationProduct->id)
        ->where('size', 'S')
        ->sole();

    expect($transfer->total_quantity)->toBe(3)
        ->and(ProductStock::withoutGlobalScope('current_store')->where('product_size_id', $small->id)->value('quantity'))->toBe(5)
        ->and(ProductStock::withoutGlobalScope('current_store')->where('product_size_id', $large->id)->value('quantity'))->toBe(4)
        ->and(ProductStock::withoutGlobalScope('current_store')->where('product_size_id', $destinationSize->id)->value('quantity'))->toBe(3)
        ->and(ProductSize::query()->where('product_id', $destinationProduct->id)->count())->toBe(1);
});

it('rolls back a selected transfer when stock is insufficient', function () {
    [$fromStore, $fromStock] = createTransferLocation('Mirzo Ulug‘bek');
    [$toStore, $toStock]     = createTransferLocation('Yakkasaroy');
    $user                    = User::factory()->create(['current_store_id' => $fromStore->id]);
    $product                 = createTransferPackageProduct($fromStore, $fromStock, 'Shampun', '300001', 2);

    $this->actingAs($user);

    expect(fn () => app(StockTransferService::class)->transfer([
        'movement_mode' => StockTransfer::MODE_SELECTED,
        'from_store_id' => $fromStore->id,
        'to_store_id'   => $toStore->id,
        'from_stock_id' => $fromStock->id,
        'to_stock_id'   => $toStock->id,
        'products'      => [[
            'product_id'       => $product->id,
            'type'             => Product::TYPE_PACKAGE,
            'package_quantity' => 3,
        ]],
    ]))->toThrow(ValidationException::class)
        ->and(ProductStock::withoutGlobalScope('current_store')->where('product_id', $product->id)->value('quantity'))->toBe(2)
        ->and(StockTransfer::query()->doesntExist())->toBeTrue()
        ->and(Product::withoutGlobalScope('current_store')->where('store_id', $toStore->id)->doesntExist())->toBeTrue();
});

/** @return array{0: Store, 1: Stock} */
function createTransferLocation(string $name): array
{
    $store = Store::query()->create(['name' => $name]);
    $stock = Stock::withoutGlobalScope('current_store')->create([
        'name'      => "{$name} ombori",
        'is_active' => true,
    ]);
    $store->stocks()->attach($stock);

    return [$store, $stock];
}

function createTransferPackageProduct(
    Store $store,
    Stock $stock,
    string $name,
    string $barcode,
    int $quantity,
): Product {
    $product = Product::withoutGlobalScope('current_store')->create([
        'store_id'      => $store->id,
        'name'          => $name,
        'barcode'       => $barcode,
        'type'          => Product::TYPE_PACKAGE,
        'initial_price' => 10_000,
        'price'         => 20_000,
    ]);
    ProductStock::withoutGlobalScope('current_store')->create([
        'product_id' => $product->id,
        'stock_id'   => $stock->id,
        'quantity'   => $quantity,
    ]);

    return $product;
}
