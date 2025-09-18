<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Stock;

class CartService
{
    protected string $key = 'pos_carts'; // Endi ko'p cart uchun

    public function all(int $cartId = 1): array
    {
        $carts = session($this->key, []);
        return $carts[$cartId] ?? [];
    }

    public function add(Product $product, int $qty = 1, int $cartId = 1): void
    {
        $carts = session($this->key, []);
        $items = $carts[$cartId] ?? [];

        $defaultStockId = Stock::where('is_main', true)->value('id');

        if (isset($items[$product->id])) {
            $items[$product->id]['qty'] += $qty;
        } else {
            $items[$product->id] = [
                'id'            => $product->id,
                'name'          => $product->name,
                'price'         => $product->price,
                'qty'           => $qty,
                'initial_price' => $product->initial_price,
                'stock_id'      => $defaultStockId,
            ];
        }

        $carts[$cartId] = $items;
        session()->put($this->key, $carts);
    }


    public function update(int $productId, int $qty, int $cartId = 1): void
    {
        $carts = session($this->key, []);
        $items = $carts[$cartId] ?? [];

        if (isset($items[$productId])) {
            $items[$productId]['qty'] = max(1, $qty);
            $carts[$cartId] = $items;
            session()->put($this->key, $carts);
        }
    }

    public function remove(int $productId, int $cartId = 1): void
    {
        $carts = session($this->key, []);
        $items = $carts[$cartId] ?? [];

        unset($items[$productId]);
        $carts[$cartId] = $items;
        session()->put($this->key, $carts);
    }

    public function clear(int $cartId = 1): void
    {
        $carts = session($this->key, []);
        unset($carts[$cartId]);
        session()->put($this->key, $carts);
    }

    public function clearAll(): void
    {
        session()->forget($this->key);
    }

    public function totals(int $cartId = 1): array
    {
        $items  = $this->all($cartId);
        $qty    = array_sum(array_column($items, 'qty'));
        $amount = array_sum(array_map(fn ($i) => $i['qty'] * $i['price'], $items));

        return ['qty' => $qty, 'amount' => $amount];
    }

    public function getAllCarts(): array
    {
        return session($this->key, []);
    }

    public function getAllCartIds(): array
    {
        $carts = session($this->key, []);
        // Barcha cartlarni qaytarish (bo'sh ham, to'la ham)
        return array_keys($carts);
    }

    public function getActiveCartIds(): array
    {
        $carts = $this->getAllCarts();
        return array_keys(array_filter($carts, fn($cart) => !empty($cart)));
    }

    public function cartExists(int $cartId): bool
    {
        $carts = session($this->key, []);
        return isset($carts[$cartId]) && !empty($carts[$cartId]);
    }

    public function updatePrice(int $productId, float $price, int $cartId = 1): void
    {
        $product = Product::find($productId);
        if (!$product) {
            return; // Mahsulot topilmasa hech narsa qilmaymiz
        }

        $minPrice = round($product->initial_price * 1.05, 2);

        if ($price < $minPrice) {
            throw new \InvalidArgumentException("Bu narx minimal narxdan past. Iltimos, minimal narxdan oshiring.");
        }

        $carts = session($this->key, []);
        $items = $carts[$cartId] ?? [];

        if (isset($items[$productId])) {
            $items[$productId]['price'] = $price;
            $carts[$cartId] = $items;
            session()->put($this->key, $carts);
        }
    }

    public function updateStock(int $productId, int $stockId, int $cartId = 1): void
    {
        $carts = session($this->key, []);
        $items = $carts[$cartId] ?? [];

        if (isset($items[$productId])) {
            $items[$productId]['stock_id'] = $stockId;
            $carts[$cartId] = $items;
            session()->put($this->key, $carts);
        }
    }


}
