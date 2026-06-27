<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Discount;
use App\Enums\DiscountType;
use Illuminate\Support\Collection;

class DiscountService
{
    /**
     * @param  array<int|string, array<string, mixed>>|Collection<int|string, array<string, mixed>>  $items
     * @return array{
     *     subtotal: float,
     *     product_discount_total: float,
     *     order_discount_total: float,
     *     discount_total: float,
     *     total: float,
     *     applied_discounts: array<int, array<string, mixed>>,
     *     items: array<int, array<string, mixed>>
     * }
     */
    public function calculate(array|Collection $items): array
    {
        $normalizedItems = $this->normalizeItems($items);
        $subtotal        = $this->roundMoney((float) $normalizedItems->sum('subtotal'));

        if ($normalizedItems->isEmpty() || $subtotal <= 0) {
            return $this->buildResponse($normalizedItems, $subtotal);
        }

        $normalizedItems = $this->attachCategoryIds($normalizedItems);

        $activeDiscounts = Discount::query()
            ->with(['products:id', 'categories:id'])
            ->activeNow()
            ->get();

        $productDiscounts = $activeDiscounts
            ->filter(fn (Discount $discount): bool => $discount->type?->isProductScope() ?? false)
            ->values();

        $discountedItems      = $this->applyProductDiscounts($normalizedItems, $productDiscounts);
        $productDiscountTotal = $this->roundMoney((float) $discountedItems->sum('product_discount_total'));
        $discountedSubtotal   = $this->roundMoney($subtotal - $productDiscountTotal);

        $orderDiscount = $this->selectBestDiscount(
            $activeDiscounts->filter(fn (Discount $discount): bool => $this->isEligibleOrderDiscount($discount, $discountedSubtotal))
        );

        $orderAppliedDiscounts = [];
        $orderDiscountTotal    = 0.0;

        if ($orderDiscount instanceof Discount) {
            $orderDiscountTotal      = $this->percentAmount($discountedSubtotal, (float) $orderDiscount->percent);
            $orderDiscountTotal      = min($orderDiscountTotal, $discountedSubtotal);
            $orderAppliedDiscounts[] = $this->serializeAppliedDiscount($orderDiscount, $orderDiscountTotal, 'order');
        }

        $productAppliedDiscounts = $this->summarizeProductAppliedDiscounts($discountedItems);
        $discountTotal           = $this->roundMoney($productDiscountTotal + $orderDiscountTotal);
        $total                   = $this->roundMoney($subtotal - $discountTotal);

        return [
            'subtotal'               => $subtotal,
            'product_discount_total' => $productDiscountTotal,
            'order_discount_total'   => $orderDiscountTotal,
            'discount_total'         => $discountTotal,
            'total'                  => max($total, 0.0),
            'applied_discounts'      => array_values(array_merge($productAppliedDiscounts, $orderAppliedDiscounts)),
            'items'                  => $discountedItems->values()->all(),
        ];
    }

    /**
     * @return array{
     *     has_discount: bool,
     *     original_price: float,
     *     discounted_price: float,
     *     discount_amount: float,
     *     applied_discount: array<string, mixed>|null
     * }
     */
    public function calculateProductLabelPrice(Product $product): array
    {
        $originalPrice = $this->roundMoney((float) ($product->price ?? 0));

        if ($originalPrice <= 0) {
            return [
                'has_discount'     => false,
                'original_price'   => $originalPrice,
                'discounted_price' => $originalPrice,
                'discount_amount'  => 0.0,
                'applied_discount' => null,
            ];
        }

        $activeProductDiscounts = Discount::query()
            ->with(['products:id', 'categories:id'])
            ->activeNow()
            ->get()
            ->filter(fn (Discount $discount): bool => $discount->type?->isProductScope() ?? false)
            ->values();

        $discount = $this->selectProductDiscountForItem(
            $activeProductDiscounts,
            (int) $product->getKey(),
            $product->category_id !== null ? (int) $product->category_id : null,
        );

        if (!$discount instanceof Discount) {
            return [
                'has_discount'     => false,
                'original_price'   => $originalPrice,
                'discounted_price' => $originalPrice,
                'discount_amount'  => 0.0,
                'applied_discount' => null,
            ];
        }

        $discountAmount  = min($this->percentAmount($originalPrice, (float) $discount->percent), $originalPrice);
        $discountedPrice = $this->roundMoney($originalPrice - $discountAmount);

        return [
            'has_discount'     => $discountAmount > 0,
            'original_price'   => $originalPrice,
            'discounted_price' => $discountedPrice,
            'discount_amount'  => $discountAmount,
            'applied_discount' => $this->serializeAppliedDiscount($discount, $discountAmount, 'product'),
        ];
    }

    /**
     * @param  array<int|string, array<string, mixed>>|Collection<int|string, array<string, mixed>>  $items
     * @return Collection<int, array<string, mixed>>
     */
    protected function normalizeItems(array|Collection $items): Collection
    {
        return collect($items)
            ->map(function (array $item, int|string $key): array {
                $productId = $item['product_id'] ?? $item['id'] ?? (is_numeric($key) ? $key : null);
                $quantity  = $this->quantityForItem($item);
                $unitPrice = (float) ($item['unit_price'] ?? $item['price'] ?? 0);
                $subtotal  = $this->roundMoney($quantity * $unitPrice);

                return [
                    'product_id'             => $productId !== null ? (int) $productId : null,
                    'product_size_id'        => isset($item['product_size_id']) ? (int) $item['product_size_id'] : null,
                    'category_id'            => isset($item['category_id']) ? (int) $item['category_id'] : null,
                    'name'                   => $item['name'] ?? null,
                    'quantity'               => $quantity,
                    'unit_price'             => $unitPrice,
                    'price'                  => $unitPrice,
                    'subtotal'               => $subtotal,
                    'product_discount_total' => 0.0,
                    'total'                  => $subtotal,
                    'applied_discounts'      => [],
                ];
            })
            ->filter(fn (array $item): bool => $item['quantity'] > 0 && $item['unit_price'] > 0)
            ->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $items
     * @return Collection<int, array<string, mixed>>
     */
    protected function attachCategoryIds(Collection $items): Collection
    {
        $productIds = $items
            ->filter(fn (array $item): bool => $item['product_id'] !== null && $item['category_id'] === null)
            ->pluck('product_id')
            ->unique()
            ->values();

        if ($productIds->isEmpty()) {
            return $items;
        }

        $categoryIdsByProductId = Product::query()
            ->whereIn('id', $productIds)
            ->pluck('category_id', 'id');

        return $items->map(function (array $item) use ($categoryIdsByProductId): array {
            if ($item['category_id'] !== null || $item['product_id'] === null) {
                return $item;
            }

            $categoryId = $categoryIdsByProductId->get($item['product_id']);

            if ($categoryId !== null) {
                $item['category_id'] = (int) $categoryId;
            }

            return $item;
        });
    }

    /**
     * @param  array<string, mixed>  $item
     */
    protected function quantityForItem(array $item): float
    {
        if (array_key_exists('quantity', $item)) {
            return max((float) $item['quantity'], 0.0);
        }

        if (!empty($item['sizes']) && is_array($item['sizes'])) {
            return (float) collect($item['sizes'])
                ->map(fn (mixed $quantity): float => max((float) $quantity, 0.0))
                ->sum();
        }

        return max((float) ($item['qty'] ?? 0), 0.0);
    }

    protected function isEligibleOrderDiscount(Discount $discount, float $discountedSubtotal): bool
    {
        if ($discount->type !== DiscountType::OrderAmountPercent) {
            return false;
        }

        if ($discount->min_order_amount === null) {
            return false;
        }

        return $discountedSubtotal >= (float) ($discount->min_order_amount ?? 0);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $items
     * @param  Collection<int, Discount>  $discounts
     * @return Collection<int, array<string, mixed>>
     */
    protected function applyProductDiscounts(Collection $items, Collection $discounts): Collection
    {
        return $items->map(function (array $item) use ($discounts): array {
            $discount = $this->selectProductDiscountForItem(
                $discounts,
                $item['product_id'],
                $item['category_id'],
            );

            if (!$discount instanceof Discount) {
                return $item;
            }

            $discountTotal = min(
                $this->percentAmount($item['subtotal'], (float) $discount->percent),
                $item['subtotal'],
            );

            $item['product_discount_total'] = $discountTotal;
            $item['total']                  = $this->roundMoney($item['subtotal'] - $discountTotal);
            $item['applied_discounts']      = [
                $this->serializeAppliedDiscount($discount, $discountTotal, 'product'),
            ];

            return $item;
        });
    }

    /**
     * @param  Collection<int, Discount>  $discounts
     */
    protected function selectProductDiscountForItem(Collection $discounts, ?int $productId, ?int $categoryId): ?Discount
    {
        $selectedProductDiscount = $this->selectBestDiscount(
            $discounts->filter(
                fn (Discount $discount): bool => $discount->type === DiscountType::SelectedProductsPercent
                    && $productId !== null
                    && $discount->products->contains('id', $productId)
            )
        );

        if ($selectedProductDiscount instanceof Discount) {
            return $selectedProductDiscount;
        }

        $categoryDiscount = $this->selectBestDiscount(
            $discounts->filter(
                fn (Discount $discount): bool => $discount->type === DiscountType::CategoryPercent
                    && $categoryId !== null
                    && $discount->categories->contains('id', $categoryId)
            )
        );

        if ($categoryDiscount instanceof Discount) {
            return $categoryDiscount;
        }

        return $this->selectBestDiscount(
            $discounts->filter(fn (Discount $discount): bool => $discount->type === DiscountType::GlobalPercent)
        );
    }

    /**
     * @param  Collection<int, Discount>  $discounts
     */
    protected function selectBestDiscount(Collection $discounts): ?Discount
    {
        return $discounts
            ->sort(function (Discount $left, Discount $right): int {
                $percentComparison = (float) $right->percent <=> (float) $left->percent;

                if ($percentComparison !== 0) {
                    return $percentComparison;
                }

                return ($left->id ?? 0) <=> ($right->id ?? 0);
            })
            ->first();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    protected function summarizeProductAppliedDiscounts(Collection $items): array
    {
        return $items
            ->flatMap(fn (array $item): array => $item['applied_discounts'])
            ->groupBy('id')
            ->map(function (Collection $rows): array {
                $discount           = $rows->first();
                $discount['amount'] = $this->roundMoney((float) $rows->sum('amount'));

                return $discount;
            })
            ->values()
            ->all();
    }

    protected function percentAmount(float $amount, float $percent): float
    {
        return $this->roundMoney($amount * $percent / 100);
    }

    protected function roundMoney(float $amount): float
    {
        return round($amount, 2);
    }

    /**
     * @return array<string, mixed>
     */
    protected function serializeAppliedDiscount(Discount $discount, float $amount, string $scope): array
    {
        return [
            'id'         => $discount->id,
            'name'       => $discount->name,
            'type'       => $discount->type?->value,
            'type_label' => $discount->type?->getLabel(),
            'percent'    => (float) $discount->percent,
            'amount'     => $this->roundMoney($amount),
            'scope'      => $scope,
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $items
     * @return array{
     *     subtotal: float,
     *     product_discount_total: float,
     *     order_discount_total: float,
     *     discount_total: float,
     *     total: float,
     *     applied_discounts: array<int, array<string, mixed>>,
     *     items: array<int, array<string, mixed>>
     * }
     */
    protected function buildResponse(Collection $items, float $subtotal): array
    {
        return [
            'subtotal'               => $subtotal,
            'product_discount_total' => 0.0,
            'order_discount_total'   => 0.0,
            'discount_total'         => 0.0,
            'total'                  => $subtotal,
            'applied_discounts'      => [],
            'items'                  => $items->values()->all(),
        ];
    }
}
