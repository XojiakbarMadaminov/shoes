<?php

namespace App\Filament\Pages;

use App\Models\Product;
use Filament\Pages\Page;
use Livewire\Attributes\On;
use App\Models\ProductStock;
use App\Services\CartService;
use App\Models\ProductSizeStock;
use Filament\Notifications\Notification;
use Filament\Panel\Concerns\HasNotifications;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Illuminate\Support\Collection as EloquentCollection;

class Pos extends Page
{
    use HasNotifications, HasPageShield;

    protected static ?string $title                          = 'Sotuv';
    protected string $view                                   = 'filament.pages.pos';
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?int $navigationSort                    = 1;

    public function getHeading(): string
    {
        return '';
    }

    public string $search     = '';
    public int $activeCartId  = 1; // Joriy faol cart ID
    public bool $showReceipt  = false; // Chek ko'rsatish uchun
    public array $receiptData = []; // Chek ma'lumotlari

    /** @var EloquentCollection<int, Product> */
    public EloquentCollection $products;

    public array $cart        = [];
    public array $totals      = ['qty' => 0, 'amount' => 0];
    public array $activeCarts = []; // Barcha faol cartlar ro'yxati

    public function mount(): void
    {
        $this->products = new EloquentCollection;
        $this->refreshActiveCarts();

        // Oxirgi faol cart ID ni session dan olish
        $savedCartId = session('pos_active_cart_id', 1);

        // Agar saqlangan cart ID hali ham mavjud bo'lsa, uni ishlatish
        if (!empty($this->activeCarts) && array_key_exists($savedCartId, $this->activeCarts)) {
            $this->activeCartId = $savedCartId;
        } else {
            // Agar saqlangan cart yo'q bo'lsa, birinchi mavjud cartni tanlash
            if (!empty($this->activeCarts)) {
                $this->activeCartId = array_key_first($this->activeCarts);
            } else {
                $this->activeCartId = 1;
            }
            session()->put('pos_active_cart_id', $this->activeCartId);
        }

        $this->refreshCart();
    }

    /* ---------- Cart boshqaruvi ---------- */
    public function switchCart(int $cartId): void
    {
        $this->activeCartId = $cartId;
        // Faol cart ID ni session ga saqlash
        session()->put('pos_active_cart_id', $cartId);

        $this->refreshCart();
        $this->reset('search');
        $this->products = new EloquentCollection;
    }

    public function createNewCart(): void
    {
        $cartService = app(CartService::class);
        $activeCarts = $cartService->getActiveCartIds();

        // Yangi cart ID ni topish
        $newCartId = 1;
        while (in_array($newCartId, $activeCarts)) {
            $newCartId++;
        }

        $this->activeCartId = $newCartId;
        // Yangi faol cart ID ni session ga saqlash
        session()->put('pos_active_cart_id', $newCartId);

        $this->refreshCart();
        $this->refreshActiveCarts();

        Notification::make()
            ->title("Yangi savat #{$newCartId} yaratildi")
            ->success()
            ->send();
    }

    public function closeCart(int $cartId): void
    {
        $cartService = app(CartService::class);

        // Faol cartlar sonini tekshirish (bo'sh cartlarni ham hisobga olish)
        $allActiveCarts = array_keys($this->activeCarts);
        if (count($allActiveCarts) <= 1) {
            Notification::make()
                ->title('Kamida bitta savat ochiq bo\'lishi kerak')
                ->warning()
                ->send();

            return;
        }

        $cartService->clear($cartId);

        // Agar yopilayotgan cart joriy faol cart bo'lsa, boshqasini tanlash
        if ($this->activeCartId === $cartId) {
            $remainingCarts     = array_filter($allActiveCarts, fn ($id) => $id !== $cartId);
            $this->activeCartId = reset($remainingCarts) ?: 1;
            session()->put('pos_active_cart_id', $this->activeCartId);
        }

        $this->refreshActiveCarts();
        $this->refreshCart();

        Notification::make()
            ->title("Savat #{$cartId} yopildi")
            ->success()
            ->send();
    }

    /* ---------- Qidiruv ---------- */
    public function updatedSearch(): void
    {
        if (empty(trim($this->search))) {
            $this->products = new EloquentCollection;

            return;
        }

        $this->products = Product::query()
            ->where(
                fn ($q) => $q->where('barcode', 'ILIKE', "%{$this->search}%")
                    ->orWhere('name', 'ILIKE', "%{$this->search}%")
            )
            ->orderBy('name')
            ->limit(15)
            ->get();
    }

    /* ---------- Savat operatsiyalari ---------- */
    public function add(int $id): void
    {
        app(CartService::class)->add(Product::findOrFail($id), 1, $this->activeCartId);
        $this->refreshCart();
        $this->refreshActiveCarts();
    }

    public function updateQty(int $id, int $qty)
    {
        $cartService = app(CartService::class);

        $cart = $cartService->all($this->activeCartId);
        $row  = $cart[$id] ?? null;

        if (!$row || empty($row['stock_id'])) {
            Notification::make()
                ->title('Avval skladni tanlang')
                ->danger()
                ->send();

            return false;
        }

        $available = ProductStock::where('product_id', $id)
            ->where('stock_id', $row['stock_id'])
            ->value('quantity');

        if ($qty > $available) {
            Notification::make()
                ->title('Yetarli miqdor yo‘q')
                ->body("Skladda faqat {$available} dona mavjud.")
                ->danger()
                ->send();

            return false;
        }

        $cartService->update($id, $qty, $this->activeCartId);

        $this->refreshCart();
        $this->refreshActiveCarts();
    }

    public function remove(int $id): void
    {
        app(CartService::class)->remove($id, $this->activeCartId);
        $this->refreshCart();
        $this->refreshActiveCarts();
    }

    /* ---------- Checkout ---------- */
    public function checkout()
    {
        $cartService = app(CartService::class);
        $cart        = $cartService->all($this->activeCartId);
        $cartItems = $cartService->all($this->activeCartId);
        $totals = $cartService->totals($this->activeCartId);

        if (empty($cart)) {
            Notification::make()
                ->title('Savat bo‘sh')
                ->danger()
                ->send();

            return false;
        }

        $this->prepareReceipt($this->activeCartId, $cartItems, $totals);

        foreach ($cart as $productId => $item) {
            // 🔹 Agar razmerli mahsulot bo‘lsa
            foreach ($item['sizes'] as $sizeId => $qty) {
                if ($qty <= 0) {
                    continue;
                }

                // 🔸 Skladdagi mavjud miqdorni tekshiramiz
                $available = ProductSizeStock::where('product_size_id', $sizeId)
                    ->where('stock_id', $item['stock_id'])
                    ->value('quantity');

                if ($qty > $available) {
                    $size = \App\Models\ProductSize::find($sizeId);
                    Notification::make()
                        ->title('Yetarli miqdor yo‘q')
                        ->body("Razmer: <b>{$size->size}</b> uchun faqat <b>{$available}</b> dona mavjud.")
                        ->danger()
                        ->send();

                    return false;
                }

                // 🔸 Agar yetarli bo‘lsa, bazadagi miqdorni kamaytiramiz
                ProductSizeStock::where('product_size_id', $sizeId)
                    ->where('stock_id', $item['stock_id'])
                    ->decrement('quantity', $qty);
            }
        }

        // 🔹 Hammasi muvaffaqiyatli bo‘lsa, savatni tozalaymiz
        session()->forget("pos_cart_{$this->activeCartId}");
        $cartService->clear($this->activeCartId);

        $this->refreshCart();
        $this->refreshActiveCarts();

        Notification::make()
            ->title("Savat #{$this->activeCartId} yakunlandi")
            ->success()
            ->send();

        return true;
    }

    /* ---------- Chek funksiyalari ---------- */
    public function prepareReceipt(int $cartId, array $items, array $totals): void
    {
        $this->receiptData = [
            'cart_id'        => $cartId,
            'items'          => $items,
            'totals'         => $totals,
            'date'           => now()->format('d.m.Y H:i:s'),
            'receipt_number' => 'R' . str_pad($cartId, 4, '0', STR_PAD_LEFT) . time(),
        ];

        $this->showReceipt = true;
    }

    public function printReceipt(): void
    {
        $this->dispatch('print-receipt');
    }

    public function closeReceipt(): void
    {
        $this->showReceipt = false;
        $this->receiptData = [];
    }

    /* ---------- Helper metodlar ---------- */
    #[
        On('refresh-cart')]
    public function refreshCart(): void
    {
        $cartService  = app(CartService::class);
        $this->cart   = $cartService->all($this->activeCartId);
        $this->totals = $cartService->totals($this->activeCartId);
    }

    public function refreshActiveCarts(): void
    {
        $cartService       = app(CartService::class);
        $this->activeCarts = [];

        // Barcha mavjud cartlarni olish (bo'sh ham, to'la ham)
        $allCartIds = $cartService->getAllCartIds();

        if (empty($allCartIds)) {
            // Agar hech qanday cart bo'lmasa, birinchi cartni yaratish
            $this->activeCarts[1] = ['qty' => 0, 'amount' => 0];
        } else {
            // Barcha cartlar uchun ma'lumotlarni olish
            foreach ($allCartIds as $cartId) {
                $this->activeCarts[$cartId] = $cartService->totals($cartId);
            }
        }
    }

    /* ---------- Skaner metodlari ---------- */
    public function scanEnter(): void
    {
        $code = trim($this->search);
        if (!$code) {
            return;
        }

        $product = Product::where('barcode', $code)->first();
        if ($product) {
            $this->add($product->id);
            $this->reset('search');
        } else {
            Notification::make()
                ->title('Mahsulot topilmadi')
                ->danger()
                ->send();
        }
    }

    public function addByBarcode(string $value): void
    {
        $value = trim($value);
        if (!$value) {
            return;
        }

        $product = Product::where('barcode', $value)
            ->orWhere(function ($q) use ($value) {
                $q->where('name', 'ILIKE', "{$value}%")
                    ->orWhere('name', 'ILIKE', "%{$value}");
            })
            ->first();

        if ($product) {
            app(CartService::class)->add($product, 1, $this->activeCartId);
            $this->reset('search');
            $this->refreshCart();
            $this->refreshActiveCarts();

            Notification::make()
                ->title("Savat #{$this->activeCartId} ga qo'shildi")
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Mahsulot topilmadi')
                ->danger()
                ->send();
        }
    }

    public function updatePrice(int $id, float $price)
    {
        try {
            app(CartService::class)->updatePrice($id, $price, $this->activeCartId);
            $this->refreshCart();
            $this->refreshActiveCarts();

            return true;
        } catch (\InvalidArgumentException $e) {
            Notification::make()
                ->title('Narx noto‘g‘ri')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return false;
        }
    }

    public function updateStock(int $productId, int $stockId): void
    {
        app(CartService::class)->updateStock($productId, $stockId, $this->activeCartId);

        $this->refreshCart();
        $this->refreshActiveCarts();
    }

    // 🔹 Mahsulot razmerlarini olish (modal uchun)
    public function getProductSizes($productId)
    {
        $product = Product::with('sizes')->findOrFail($productId);

        $sizes = $product->sizes->map(fn ($s) => [
            'id'        => $s->id,
            'name'      => $s->size,
            'available' => ProductSizeStock::where('product_size_id', $s->id)
                ->where('stock_id', $this->activeStockId ?? 1)
                ->value('quantity') ?? 0,
        ]);

        return [
            'product'    => ['id' => $product->id, 'name' => $product->name],
            'sizes'      => $sizes,
            'quantities' => $this->cart[$productId]['sizes'] ?? [],
        ];
    }

    public function updateSizes(int $productId, array $sizes)
    {
        $cartService = app(CartService::class);
        $cart        = $cartService->all($this->activeCartId);
        $row         = $cart[$productId] ?? null;

        if (!$row || empty($row['stock_id'])) {
            Notification::make()
                ->title('Avval skladni tanlang')
                ->danger()
                ->send();

            return false;
        }

        $stockId = $row['stock_id'];

        foreach ($sizes as $sizeId => $qty) {
            if ($qty <= 0) {
                continue;
            }

            $size      = \App\Models\ProductSize::find($sizeId);
            $available = ProductSizeStock::where('product_size_id', $sizeId)
                ->where('stock_id', $stockId)
                ->value('quantity');

            if ($qty > $available) {
                Notification::make()
                    ->title('Yetarli miqdor yo‘q')
                    ->body("Razmer: <b>{$size->size}</b> uchun faqat <b>{$available}</b> dona mavjud.")
                    ->danger()
                    ->send();

                return false;
            }
        }

        // 🔹 Hammasi to‘g‘ri — saqlaymiz
        $cartService->updateSizes($productId, $sizes, $this->activeCartId);

        $this->refreshCart();
        $this->refreshActiveCarts();
    }
}
