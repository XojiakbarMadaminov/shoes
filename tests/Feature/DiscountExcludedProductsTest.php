<?php

use App\Models\Product;
use App\Models\Discount;
use App\Enums\DiscountType;
use App\Services\DiscountService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('does not apply a global discount to excluded products', function () {
    $excludedProduct = Product::factory()->create(['price' => 100_000]);
    $regularProduct  = Product::factory()->create(['price' => 100_000]);
    $discount        = Discount::factory()->create([
        'type'    => DiscountType::GlobalPercent,
        'percent' => 10,
    ]);

    $discount->excludedProducts()->attach($excludedProduct);

    $result = app(DiscountService::class)->calculate([
        ['product_id' => $excludedProduct->id, 'quantity' => 1, 'price' => 100_000],
        ['product_id' => $regularProduct->id, 'quantity' => 1, 'price' => 100_000],
    ]);

    expect($result['product_discount_total'])->toBe(10_000.0)
        ->and($result['total'])->toBe(190_000.0)
        ->and($result['items'][0]['product_discount_total'])->toBe(0.0)
        ->and($result['items'][0]['applied_discounts'])->toBe([])
        ->and($result['items'][1]['product_discount_total'])->toBe(10_000.0);
});

it('does not show a discounted label price for a product excluded from a global discount', function () {
    $product  = Product::factory()->create(['price' => 100_000]);
    $discount = Discount::factory()->create([
        'type'    => DiscountType::GlobalPercent,
        'percent' => 10,
    ]);

    $discount->excludedProducts()->attach($product);

    $result = app(DiscountService::class)->calculateProductLabelPrice($product);

    expect($result['has_discount'])->toBeFalse()
        ->and($result['original_price'])->toBe(100_000.0)
        ->and($result['discounted_price'])->toBe(100_000.0)
        ->and($result['discount_amount'])->toBe(0.0);
});

it('can still apply another eligible product discount to a globally excluded product', function () {
    $product        = Product::factory()->create(['price' => 100_000]);
    $globalDiscount = Discount::factory()->create([
        'type'    => DiscountType::GlobalPercent,
        'percent' => 10,
    ]);
    $selectedDiscount = Discount::factory()->create([
        'type'    => DiscountType::SelectedProductsPercent,
        'percent' => 20,
    ]);

    $globalDiscount->excludedProducts()->attach($product);
    $selectedDiscount->products()->attach($product);

    $result = app(DiscountService::class)->calculate([
        ['product_id' => $product->id, 'quantity' => 1, 'price' => 100_000],
    ]);

    expect($result['product_discount_total'])->toBe(20_000.0)
        ->and($result['total'])->toBe(80_000.0)
        ->and($result['applied_discounts'][0]['id'])->toBe($selectedDiscount->id);
});
