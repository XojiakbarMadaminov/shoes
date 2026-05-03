<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\Product;

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
            // Agar mahsulot package tipida bo'lsa, qty ni oshirish
            // Agar razmerli bo'lsa, sizes yig'indisi prioritet
            if (($product->type ?? 'size') === 'package') {
                $items[$product->id]['qty'] += $qty;
            }
            // Razmerli mahsulotlar uchun hech narsa qilmaymiz - sizes yig'indisi ishlatiladi
        } else {
            $items[$product->id] = [
                'id'            => $product->id,
                'name'          => $product->name,
                'yuan_price'    => $product->yuan_price,
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
            $carts[$cartId]           = $items;
            session()->put($this->key, $carts);
        }
    }

    public function updateSizes(int $productId, array $sizes, int $cartId = 1): void
    {
        $carts = session($this->key, []);
        $items = $carts[$cartId] ?? [];

        if (isset($items[$productId])) {
            // 🔹 Razmerlarni va umumiy qty ni yangilaymiz
            $items[$productId]['sizes'] = $sizes;
            $items[$productId]['qty']   = max(1, array_sum($sizes));

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
        $qty    = 0;
        $amount = 0;

        foreach ($items as $item) {
            // Agar mahsulotda sizes mavjud bo'lsa, sizes yig'indisini ishlatish
            if (!empty($item['sizes'])) {
                $itemQty = array_sum($item['sizes']);
            } else {
                // Package yoki sizes mavjud bo'lmasa, oddiy qty dan foydalanish
                $itemQty = (int) ($item['qty'] ?? 0);
            }

            $qty    += $itemQty;
            $amount += $itemQty * (float) ($item['price'] ?? 0);
        }

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

        return array_keys(array_filter($carts, fn ($cart) => !empty($cart)));
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
            throw new \InvalidArgumentException('Bu narx minimal narxdan past. Iltimos, minimal narxdan oshiring.');
        }

        $carts = session($this->key, []);
        $items = $carts[$cartId] ?? [];

        if (isset($items[$productId])) {
            $items[$productId]['price'] = $price;
            $carts[$cartId]             = $items;
            session()->put($this->key, $carts);
        }
    }

    public function updateStock(int $productId, int $stockId, int $cartId = 1): void
    {
        $carts = session($this->key, []);
        $items = $carts[$cartId] ?? [];

        if (isset($items[$productId])) {
            $items[$productId]['stock_id'] = $stockId;
            $carts[$cartId]                = $items;
            session()->put($this->key, $carts);
        }
    }
}
