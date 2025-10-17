<?php

namespace App\Filament\Pages;

use App\Models\Client;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Debtor;
use App\Models\SaleItem;
use Filament\Pages\Page;
use Livewire\Attributes\On;
use App\Services\CartService;
use App\Models\ProductSizeStock;
use App\Models\DebtorTransaction;
use Filament\Notifications\Notification;
use Filament\Panel\Concerns\HasNotifications;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Illuminate\Support\Facades\DB;
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
    public array $cartClients = [];
    public array $cartPaymentTypes = [];
    public bool $showClientPanel = false;
    public bool $showCreateClientForm = false;
    public string $searchClient = '';
    public $clients = [];
    public ?int $selectedClientId = null;
    public string $paymentType = '';

    public array $newClient = [
        'full_name' => '',
        'phone' => '',
    ];

    public function mount(): void
    {
        $this->products = new EloquentCollection;
        $this->refreshActiveCarts();

        $this->cartClients       = session('pos_cart_clients', []);
        $this->cartPaymentTypes  = session('pos_cart_payment_types', []);

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

        $this->loadActiveCartMeta();
        $this->refreshCart();
    }

    /* ---------- Cart boshqaruvi ---------- */
    public function switchCart(int $cartId): void
    {
        $this->activeCartId = $cartId;
        // Faol cart ID ni session ga saqlash
        session()->put('pos_active_cart_id', $cartId);
        $this->loadActiveCartMeta();

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
        unset($this->cartClients[$newCartId], $this->cartPaymentTypes[$newCartId]);
        $this->loadActiveCartMeta();
        $this->persistCartMeta();

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
        unset($this->cartClients[$cartId], $this->cartPaymentTypes[$cartId]);

        // Agar yopilayotgan cart joriy faol cart bo'lsa, boshqasini tanlash
        if ($this->activeCartId === $cartId) {
            $remainingCarts     = array_filter($allActiveCarts, fn ($id) => $id !== $cartId);
            $this->activeCartId = reset($remainingCarts) ?: 1;
            session()->put('pos_active_cart_id', $this->activeCartId);
        }

        $this->loadActiveCartMeta();
        $this->persistCartMeta();

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
        $totals      = $cartService->totals($this->activeCartId);

        if (empty($cart)) {
            Notification::make()
                ->title('Savat bo‘sh')
                ->danger()
                ->send();

            return false;
        }

        if (!$this->selectedClientId) {
            Notification::make()
                ->title('Klient tanlanmagan')
                ->body('Checkout qilishdan oldin klientni tanlang.')
                ->warning()
                ->send();

            return false;
        }

        if (!$this->paymentType) {
            Notification::make()
                ->title('To‘lov turi tanlanmagan')
                ->body('Checkout qilishdan oldin to‘lov turini tanlang.')
                ->warning()
                ->send();

            return false;
        }

        $validPaymentTypes = ['cash', 'card', 'debt', 'transfer'];

        if (!in_array($this->paymentType, $validPaymentTypes, true)) {
            Notification::make()
                ->title('Noto‘g‘ri to‘lov turi')
                ->danger()
                ->send();

            return false;
        }

        if ($this->paymentType === 'debt' && !$this->selectedClientId) {
            Notification::make()
                ->title('Klient talab qilinadi')
                ->body('Qarzga savdo uchun klientni tanlashingiz kerak.')
                ->warning()
                ->send();

            return false;
        }

        $preparedItems = [];

        foreach ($cart as $productId => $item) {
            $stockId = $item['stock_id'] ?? null;

            if (!$stockId) {
                Notification::make()
                    ->title('Sklad tanlanmagan')
                    ->body("{$item['name']} uchun sklad tanlang.")
                    ->warning()
                    ->send();

                return false;
            }

            $sizes = $item['sizes'] ?? [];

            if (empty($sizes)) {
                Notification::make()
                    ->title('Razmer tanlanmagan')
                    ->body("{$item['name']} uchun razmer va miqdorlarni kiriting.")
                    ->warning()
                    ->send();

                return false;
            }

            $hasPositiveQty = false;

            foreach ($sizes as $sizeId => $qty) {
                $qty = (int) $qty;

                if ($qty <= 0) {
                    continue;
                }

                $hasPositiveQty = true;

                $available = ProductSizeStock::where('product_size_id', $sizeId)
                    ->where('stock_id', $stockId)
                    ->value('quantity');

                if ($available === null || $qty > $available) {
                    $size = \App\Models\ProductSize::find($sizeId);
                    $sizeName = $size?->size ?? 'Razmer';
                    $availableQty = $available ?? 0;

                    Notification::make()
                        ->title('Yetarli miqdor yo‘q')
                        ->body("{$item['name']} ({$sizeName}) uchun maksimal {$availableQty} dona mavjud.")
                        ->danger()
                        ->send();

                    return false;
                }

                $preparedItems[] = [
                    'product_id'      => $item['id'] ?? $productId,
                    'stock_id'        => $stockId,
                    'product_size_id' => (int) $sizeId,
                    'quantity'        => $qty,
                    'price'           => (float) $item['price'],
                    'name'            => $item['name'] ?? 'Mahsulot',
                ];
            }

            if (!$hasPositiveQty) {
                Notification::make()
                    ->title('Miqdor kiritilmagan')
                    ->body("{$item['name']} uchun sotiladigan miqdorni kiriting.")
                    ->warning()
                    ->send();

                return false;
            }
        }

        if (empty($preparedItems)) {
            Notification::make()
                ->title('Miqdor kiritilmagan')
                ->body('Har bir mahsulot uchun sotiladigan miqdorni kiriting.')
                ->warning()
                ->send();

            return false;
        }

        $totalAmount   = round((float) ($totals['amount'] ?? 0), 2);
        $receiptItems  = array_values($cart);
        $paymentType   = $this->paymentType;
        $clientId      = $this->selectedClientId;

        try {
            DB::transaction(function () use ($preparedItems, $totalAmount, $paymentType, $clientId) {
                $paidAmount      = $paymentType === 'debt' ? 0.0 : $totalAmount;
                $remainingAmount = round($totalAmount - $paidAmount, 2);

                $sale = Sale::create([
                    'cart_id'          => $this->activeCartId,
                    'client_id'        => $clientId,
                    'total_amount'     => $totalAmount,
                    'paid_amount'      => $paidAmount,
                    'remaining_amount' => $remainingAmount,
                    'payment_type'     => $paymentType,
                ]);

                foreach ($preparedItems as $prepared) {
                    $lineTotal = round($prepared['quantity'] * $prepared['price'], 2);

                    SaleItem::create([
                        'sale_id'         => $sale->id,
                        'product_id'      => $prepared['product_id'],
                        'stock_id'        => $prepared['stock_id'],
                        'product_size_id' => $prepared['product_size_id'],
                        'quantity'        => $prepared['quantity'],
                        'price'           => $prepared['price'],
                        'total'           => $lineTotal,
                    ]);

                    ProductSizeStock::where('product_size_id', $prepared['product_size_id'])
                        ->where('stock_id', $prepared['stock_id'])
                        ->decrement('quantity', $prepared['quantity']);
                }

                if ($paymentType === 'debt') {
                    $user    = auth()->user();
                    $storeId = $user?->current_store_id;

                    if (!$storeId) {
                        throw new \RuntimeException('Foydalanuvchi uchun joriy do‘kon tanlanmagan.');
                    }

                    $debtor = Debtor::firstOrCreate(
                        [
                            'store_id'  => $storeId,
                            'client_id' => $clientId,
                        ],
                        [
                            'amount'   => 0,
                            'currency' => 'UZS',
                            'date'     => now()->toDateString(),
                        ]
                    );

                    $addedAmount = (int) round($totalAmount);
                    $debtor->increment('amount', $addedAmount);

                    DebtorTransaction::create([
                        'debtor_id' => $debtor->id,
                        'amount'    => $addedAmount,
                        'type'      => 'debt',
                        'date'      => now()->toDateString(),
                        'note'      => "Sotuv #{$sale->id}",
                    ]);
                }
            });
        } catch (\Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Checkout amalga oshmadi')
                ->body($exception->getMessage())
                ->danger()
                ->send();

            return false;
        }

        $this->prepareReceipt($this->activeCartId, $receiptItems, $totals);

        $cartService->clear($this->activeCartId);

        unset($this->cartClients[$this->activeCartId], $this->cartPaymentTypes[$this->activeCartId]);
        $this->persistCartMeta();
        $this->loadActiveCartMeta();

        $this->refreshCart();
        $this->refreshActiveCarts();

        $this->showClientPanel = false;

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

    protected function loadActiveCartMeta(): void
    {
        $this->selectedClientId = $this->cartClients[$this->activeCartId] ?? null;
        $this->paymentType      = $this->cartPaymentTypes[$this->activeCartId] ?? '';
    }

    protected function persistCartMeta(): void
    {
        session()->put('pos_cart_clients', $this->cartClients);
        session()->put('pos_cart_payment_types', $this->cartPaymentTypes);
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

    /* === Klient panelini ochish === */
    public function openClientPanel(): void
    {
        $this->showClientPanel = true;
        $this->loadClients();
    }

    /* === Klientlarni qidirish === */
    public function loadClients(): void
    {
        $this->clients = Client::query()
            ->when($this->searchClient, fn ($q) => $q->where('full_name', 'ilike', "%{$this->searchClient}%")
                ->orWhere('phone', 'ilike', "%{$this->searchClient}%"))
            ->orderBy('full_name')
            ->limit(20)
            ->get();
    }

    public function updatedSearchClient(): void
    {
        $this->loadClients();
    }

    /* === Klient tanlash === */
    public function selectClient(int $id): void
    {
        $this->selectedClientId = $id;
        $this->cartClients[$this->activeCartId] = $id;
        $this->persistCartMeta();

        Notification::make()
            ->title('Klient tanlandi')
            ->body(Client::find($id)?->full_name)
            ->success()
            ->send();
    }

    /* === Yangi klient formasi === */
    public function toggleCreateClientForm(): void
    {
        $this->showCreateClientForm = !$this->showCreateClientForm;

        // Formani ochganda eski ma'lumotlarni tozalash
        if ($this->showCreateClientForm) {
            $this->newClient = ['full_name' => '', 'phone' => ''];
        }
    }

    /* === Yangi klient yaratish === */
    public function createClient(): void
    {
        $this->validate([
            'newClient.full_name' => 'required|string|min:3',
            'newClient.phone'     => 'nullable|string|min:7',
        ], [
            'newClient.full_name.required' => 'To\'liq ismni kiriting',
            'newClient.full_name.min'      => 'Ism kamida 3 ta belgidan iborat bo\'lishi kerak',
            'newClient.phone.min'          => 'Telefon raqam kamida 7 ta raqamdan iborat bo\'lishi kerak',
        ]);

        $client = Client::create([
            'full_name' => $this->newClient['full_name'],
            'phone'     => $this->newClient['phone'] ?: null,
        ]);

        $this->selectedClientId     = $client->id;
        $this->cartClients[$this->activeCartId] = $client->id;
        $this->newClient            = ['full_name' => '', 'phone' => ''];
        $this->showCreateClientForm = false;
        $this->loadClients();
        $this->persistCartMeta();

        Notification::make()
            ->title('Yangi klient yaratildi')
            ->body("{$client->full_name} qo'shildi va tanlandi")
            ->success()
            ->send();
    }

    /* === To'lov turini tanlash === */
    public function selectPaymentType(string $type): void
    {
        if (!in_array($type, ['cash', 'card', 'debt', 'transfer'])) {
            Notification::make()
                ->title('Noto\'g\'ri to\'lov turi')
                ->danger()
                ->send();

            return;
        }

        $this->paymentType = $type;
        $this->cartPaymentTypes[$this->activeCartId] = $type;
        $this->persistCartMeta();

        Notification::make()
            ->title('To\'lov turi tanlandi')
            ->body(match ($type) {
                'card'     => '💳 Karta',
                'cash'     => '💵 Naqd',
                'transfer' => '🏦 O\'tkazma',
                'debt'     => '📋 Qarz',
                default    => 'Tanlandi',
            })
            ->success()
            ->send();

        // Panelni yopmaymiz - foydalanuvchi o'zi yopsin yoki checkout qilsin
    }
}
