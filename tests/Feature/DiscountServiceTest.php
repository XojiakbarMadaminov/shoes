<?php

use App\Models\Product;
use App\Models\Category;
use App\Models\Discount;
use App\Enums\DiscountType;
use App\Services\DiscountService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('applies selected product discount before order amount discount', function () {
    $selectedProduct = Product::factory()->create(['price' => 200000]);
    $regularProduct  = Product::factory()->create(['price' => 400000]);

    $selectedDiscount = Discount::factory()->create([
        'type'    => DiscountType::SelectedProductsPercent,
        'percent' => 50,
    ]);
    $selectedDiscount->products()->attach($selectedProduct);

    Discount::factory()->create([
        'type'             => DiscountType::OrderAmountPercent,
        'percent'          => 10,
        'min_order_amount' => 500000,
    ]);

    $result = app(DiscountService::class)->calculate([
        ['product_id' => $selectedProduct->id, 'quantity' => 1, 'price' => 200000],
        ['product_id' => $regularProduct->id, 'quantity' => 1, 'price' => 400000],
    ]);

    expect($result['subtotal'])->toBe(600000.0)
        ->and($result['product_discount_total'])->toBe(100000.0)
        ->and($result['order_discount_total'])->toBe(50000.0)
        ->and($result['discount_total'])->toBe(150000.0)
        ->and($result['total'])->toBe(450000.0);
});

it('checks order amount discounts against discounted subtotal only', function () {
    $selectedProduct = Product::factory()->create(['price' => 200000]);
    $regularProduct  = Product::factory()->create(['price' => 350000]);

    $selectedDiscount = Discount::factory()->create([
        'type'    => DiscountType::SelectedProductsPercent,
        'percent' => 50,
    ]);
    $selectedDiscount->products()->attach($selectedProduct);

    Discount::factory()->create([
        'type'             => DiscountType::OrderAmountPercent,
        'percent'          => 10,
        'min_order_amount' => 500000,
    ]);

    $result = app(DiscountService::class)->calculate([
        ['product_id' => $selectedProduct->id, 'quantity' => 1, 'price' => 200000],
        ['product_id' => $regularProduct->id, 'quantity' => 1, 'price' => 350000],
    ]);

    expect($result['subtotal'])->toBe(550000.0)
        ->and($result['product_discount_total'])->toBe(100000.0)
        ->and($result['order_discount_total'])->toBe(0.0)
        ->and($result['total'])->toBe(450000.0);
});

it('applies product discounts by selected category global precedence', function () {
    $category = Category::create([
        'name'      => 'Krossovka',
        'is_active' => true,
    ]);

    $selectedProduct = Product::factory()->create([
        'price'       => 100000,
        'category_id' => $category->id,
    ]);

    $categoryProduct = Product::factory()->create([
        'price'       => 100000,
        'category_id' => $category->id,
    ]);

    $globalProduct = Product::factory()->create(['price' => 100000]);

    $selectedDiscount = Discount::factory()->create([
        'type'    => DiscountType::SelectedProductsPercent,
        'percent' => 50,
    ]);
    $selectedDiscount->products()->attach($selectedProduct);

    $categoryDiscount = Discount::factory()->create([
        'type'    => DiscountType::CategoryPercent,
        'percent' => 20,
    ]);
    $categoryDiscount->categories()->attach($category);

    Discount::factory()->create([
        'type'    => DiscountType::GlobalPercent,
        'percent' => 10,
    ]);

    $result = app(DiscountService::class)->calculate([
        ['product_id' => $selectedProduct->id, 'quantity' => 1, 'price' => 100000],
        ['product_id' => $categoryProduct->id, 'quantity' => 1, 'price' => 100000],
        ['product_id' => $globalProduct->id, 'quantity' => 1, 'price' => 100000],
    ]);

    expect($result['product_discount_total'])->toBe(80000.0)
        ->and($result['total'])->toBe(220000.0)
        ->and($result['items'][0]['product_discount_total'])->toBe(50000.0)
        ->and($result['items'][1]['product_discount_total'])->toBe(20000.0)
        ->and($result['items'][2]['product_discount_total'])->toBe(10000.0);
});

it('uses the highest percent discount when duplicate discounts exist in the same precedence level', function () {
    $product = Product::factory()->create(['price' => 100000]);

    Discount::factory()->create([
        'type'    => DiscountType::GlobalPercent,
        'percent' => 5,
    ]);

    $winningDiscount = Discount::factory()->create([
        'type'    => DiscountType::GlobalPercent,
        'percent' => 15,
    ]);

    $result = app(DiscountService::class)->calculate([
        ['product_id' => $product->id, 'quantity' => 1, 'price' => 100000],
    ]);

    expect($result['product_discount_total'])->toBe(15000.0)
        ->and($result['total'])->toBe(85000.0)
        ->and($result['applied_discounts'][0]['id'])->toBe($winningDiscount->id);
});

it('ignores inactive and expired discounts', function () {
    $product = Product::factory()->create(['price' => 100000]);

    Discount::factory()->create([
        'type'      => DiscountType::GlobalPercent,
        'percent'   => 50,
        'is_active' => false,
    ]);

    Discount::factory()->create([
        'type'    => DiscountType::GlobalPercent,
        'percent' => 40,
        'ends_at' => now()->subMinute(),
    ]);

    Discount::factory()->create([
        'type'      => DiscountType::GlobalPercent,
        'percent'   => 10,
        'starts_at' => now()->subMinute(),
        'ends_at'   => now()->addMinute(),
    ]);

    $result = app(DiscountService::class)->calculate([
        ['product_id' => $product->id, 'quantity' => 1, 'price' => 100000],
    ]);

    expect($result['product_discount_total'])->toBe(10000.0)
        ->and($result['total'])->toBe(90000.0);
});

it('applies selected product discounts only to attached products', function () {
    $selectedProduct = Product::factory()->create(['price' => 100000]);
    $regularProduct  = Product::factory()->create(['price' => 100000]);

    $discount = Discount::factory()->create([
        'type'    => DiscountType::SelectedProductsPercent,
        'percent' => 50,
    ]);
    $discount->products()->attach($selectedProduct);

    $result = app(DiscountService::class)->calculate([
        ['product_id' => $selectedProduct->id, 'quantity' => 1, 'price' => 100000],
        ['product_id' => $regularProduct->id, 'quantity' => 1, 'price' => 100000],
    ]);

    expect($result['product_discount_total'])->toBe(50000.0)
        ->and($result['total'])->toBe(150000.0)
        ->and($result['items'][0]['product_discount_total'])->toBe(50000.0)
        ->and($result['items'][1]['product_discount_total'])->toBe(0.0);
});

it('calculates product label discount price without order amount discounts', function () {
    $product = Product::factory()->create(['price' => 100000]);

    $selectedDiscount = Discount::factory()->create([
        'type'    => DiscountType::SelectedProductsPercent,
        'percent' => 50,
    ]);
    $selectedDiscount->products()->attach($product);

    Discount::factory()->create([
        'type'             => DiscountType::OrderAmountPercent,
        'percent'          => 10,
        'min_order_amount' => 1,
    ]);

    $result = app(DiscountService::class)->calculateProductLabelPrice($product);

    expect($result['has_discount'])->toBeTrue()
        ->and($result['original_price'])->toBe(100000.0)
        ->and($result['discounted_price'])->toBe(50000.0)
        ->and($result['discount_amount'])->toBe(50000.0);
});
