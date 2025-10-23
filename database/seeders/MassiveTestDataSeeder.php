<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\Sale;
use App\Models\User;
use Faker\Generator;
use App\Models\Color;
use App\Models\Stock;
use App\Models\Store;
use App\Models\Client;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Category;
use App\Models\Purchase;
use App\Models\SaleItem;
use App\Models\Supplier;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Models\ProductStock;
use App\Models\PurchaseItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MassiveTestDataSeeder extends Seeder
{
    private Generator $faker;

    private array $storeIds             = [];
    private array $stockIds             = [];
    private array $storeStocks          = [];
    private array $storeUsers           = [];
    private array $userIds              = [];
    private array $clientIds            = [];
    private array $supplierIds          = [];
    private array $categoryIds          = [];
    private array $colorIds             = [];
    private array $productIds           = [];
    private array $productPrices        = [];
    private array $productInitialPrices = [];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::disableQueryLog();

        $this->faker = fake();
        $this->faker->unique(true);

        $this->seedStoresAndStocks();
        $this->seedColors();
        $this->seedCategories();
        $this->seedUsers();
        $this->seedClients();
        $this->seedSuppliers();
        $this->seedProductsAndStocks();
        $this->seedSales();
        $this->seedPurchases();
        $this->seedExpenses();

        $this->command?->info('✅ Massive test dataset generated successfully.');
    }

    private function seedStoresAndStocks(): void
    {
        $targetStores   = 3;
        $existingStores = Store::count();
        $storesToCreate = max(0, $targetStores - $existingStores);

        for ($i = 1; $i <= $storesToCreate; $i++) {
            $index     = $existingStores + $i;
            $createdAt = Carbon::instance($this->faker->dateTimeBetween('-2 years', 'now'));

            $store = Store::query()->create([
                'name'       => 'Demo Store ' . $index,
                'address'    => $this->faker->address(),
                'phone'      => $this->faker->unique()->numerify('+998##-###-##-##'),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            $this->ensureStoreStocks($store, 2);
        }

        // Ensure every store has at least two active stocks
        $stores = Store::all();
        foreach ($stores as $store) {
            $this->ensureStoreStocks($store, 2);
        }

        $stores            = Store::with('stocks')->get();
        $this->storeIds    = $stores->pluck('id')->all();
        $this->stockIds    = $stores->flatMap(fn ($store) => $store->stocks->pluck('id'))->unique()->values()->all();
        $this->storeStocks = $stores->mapWithKeys(fn ($store) => [$store->id => $store->stocks->pluck('id')->all()])->toArray();
        $this->faker->unique(true);
    }

    private function ensureStoreStocks(Store $store, int $minimum): void
    {
        $currentCount = $store->stocks()->count();
        $needed       = max(0, $minimum - $currentCount);

        for ($i = 1; $i <= $needed; $i++) {
            $index = $currentCount + $i;
            $stock = Stock::query()->firstOrCreate(
                ['name' => sprintf('%s Stock %d', $store->name, $index)],
                [
                    'is_main'    => $currentCount === 0 && $i === 1,
                    'is_active'  => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $store->stocks()->syncWithoutDetaching([$stock->id]);
        }
    }

    private function seedColors(): void
    {
        $target = 12;

        while (Color::count() < $target) {
            $title = Str::title($this->faker->unique()->safeColorName());

            Color::query()->firstOrCreate(
                ['title' => $title],
                [
                    'status'     => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->colorIds = Color::pluck('id')->all();
        $this->faker->unique(true);
    }

    private function seedCategories(): void
    {
        $target = 15;

        while (Category::count() < $target) {
            $name = 'Category ' . Str::title($this->faker->unique()->words(2, true));

            Category::query()->firstOrCreate(
                ['name' => $name],
                [
                    'is_active'  => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->categoryIds = Category::pluck('id')->all();
        $this->faker->unique(true);
    }

    private function seedUsers(): void
    {
        $targetUsers  = 5;
        $currentUsers = User::count();

        if ($currentUsers < $targetUsers) {
            $needed   = $targetUsers - $currentUsers;
            $newUsers = User::factory()->count($needed)->create();

            foreach ($newUsers as $user) {
                $storeAssignments = $this->randomStoreAssignments();
                foreach ($storeAssignments as $storeId) {
                    $user->stores()->syncWithoutDetaching([
                        $storeId => ['role' => Arr::random(['cashier', 'manager', 'admin'])],
                    ]);
                }

                if (!empty($storeAssignments)) {
                    $user->current_store_id = Arr::random($storeAssignments);
                    $user->save();
                }
            }
        }

        // Ensure all users have at least one store linked and current store id
        $allUsers = User::all();
        foreach ($allUsers as $user) {
            $attachedStoreIds = $user->stores()->pluck('store_id')->all();

            if (empty($attachedStoreIds)) {
                $storeAssignments = $this->randomStoreAssignments();
                foreach ($storeAssignments as $storeId) {
                    $user->stores()->syncWithoutDetaching([
                        $storeId => ['role' => Arr::random(['cashier', 'manager', 'admin'])],
                    ]);
                }
                $attachedStoreIds = $storeAssignments;
            }

            if (!$user->current_store_id && !empty($attachedStoreIds)) {
                $user->current_store_id = Arr::random($attachedStoreIds);
                $user->save();
            }
        }

        $this->refreshStoreUsers();
    }

    private function randomStoreAssignments(): array
    {
        if (empty($this->storeIds)) {
            return [];
        }

        $count    = min(count($this->storeIds), $this->faker->numberBetween(1, max(1, count($this->storeIds))));
        $selected = Arr::random($this->storeIds, $count);

        return is_array($selected) ? $selected : [$selected];
    }

    private function refreshStoreUsers(): void
    {
        $stores = Store::with('users')->get();

        $this->storeUsers = $stores
            ->mapWithKeys(fn ($store) => [$store->id => $store->users->pluck('id')->all()])
            ->toArray();

        $this->userIds = User::pluck('id')->all();

        foreach ($this->storeIds as $storeId) {
            if (empty($this->storeUsers[$storeId] ?? [])) {
                $user = User::inRandomOrder()->first();

                if ($user) {
                    $user->stores()->syncWithoutDetaching([
                        $storeId => ['role' => Arr::random(['cashier', 'manager', 'admin'])],
                    ]);
                }
            }
        }

        $this->storeUsers = Store::with('users')
            ->get()
            ->mapWithKeys(fn ($store) => [$store->id => $store->users->pluck('id')->all()])
            ->toArray();

        $this->userIds = User::pluck('id')->all();
    }

    private function seedClients(): void
    {
        $target = 5000;
        $needed = max(0, $target - Client::count());

        if ($needed > 0) {
            $this->faker->unique(true);
            Client::factory()->count($needed)->create();
        }

        $this->clientIds = Client::pluck('id')->all();
        $this->faker->unique(true);
    }

    private function seedSuppliers(): void
    {
        $target = 100;
        $needed = max(0, $target - Supplier::count());

        if ($needed > 0) {
            $this->faker->unique(true);
            Supplier::factory()->count($needed)->create();
        }

        $this->supplierIds = Supplier::pluck('id')->all();
        $this->faker->unique(true);
    }

    private function seedProductsAndStocks(): void
    {
        if (empty($this->categoryIds) || empty($this->colorIds)) {
            return;
        }

        $target = 15000;
        $needed = max(0, $target - Product::count());

        if ($needed > 0) {
            $categoryIds = $this->categoryIds;
            $colorIds    = $this->colorIds;

            $this->faker->unique(true);
            Product::factory()
                ->count($needed)
                ->state(fn () => [
                    'category_id' => Arr::random($categoryIds),
                    'color_id'    => Arr::random($colorIds),
                    'type'        => 'package',
                ])
                ->create();
        }

        $this->productPrices = Product::pluck('price', 'id')
            ->map(fn ($value) => (float) $value)
            ->all();

        $this->productInitialPrices = Product::pluck('initial_price', 'id')
            ->map(fn ($value) => (float) $value)
            ->all();

        $this->productIds = array_keys($this->productPrices);

        if (!empty($this->stockIds) && !empty($this->productIds)) {
            Product::query()->chunkById(500, function ($products): void {
                $rows = [];
                $now  = now();

                foreach ($products as $product) {
                    foreach ($this->stockIds as $stockId) {
                        $rows[] = [
                            'product_id'      => $product->id,
                            'product_size_id' => null,
                            'stock_id'        => $stockId,
                            'quantity'        => $this->faker->numberBetween(20, 400),
                            'created_at'      => $now,
                            'updated_at'      => $now,
                        ];
                    }
                }

                ProductStock::query()->upsert(
                    $rows,
                    ['product_id', 'product_size_id', 'stock_id'],
                    ['quantity', 'updated_at']
                );
            });
        }
    }

    private function seedSales(): void
    {
        $target = 20000;
        $needed = max(0, $target - Sale::count());

        if (
            $needed <= 0 ||
            empty($this->storeIds) ||
            empty($this->productIds)
        ) {
            return;
        }

        $cartCounter = (int) (Sale::max('cart_id') ?? 100000);

        $statusWeights = [
            Sale::STATUS_COMPLETED => 70,
            Sale::STATUS_PENDING   => 20,
            Sale::STATUS_REJECTED  => 10,
        ];

        $paymentCompleted = ['cash', 'card', 'transfer', 'debt', 'partial', 'mixed'];
        $paymentPending   = ['preorder', 'debt', 'partial'];
        $paymentRejected  = ['cash', 'card', 'transfer'];

        for ($i = 0; $i < $needed; $i++) {
            $cartCounter++;
            $storeId  = Arr::random($this->storeIds);
            $stockIds = $this->storeStocks[$storeId] ?? $this->stockIds;

            if (empty($stockIds)) {
                continue;
            }

            $userCandidates = $this->storeUsers[$storeId] ?? $this->userIds;
            if (empty($userCandidates)) {
                $userCandidates = $this->userIds;
            }

            if (empty($userCandidates)) {
                continue;
            }

            $createdBy = Arr::random($userCandidates);
            $saleDate  = Carbon::instance($this->faker->dateTimeBetween('-18 months', 'now'));
            $status    = $this->weightedRandom($statusWeights);

            $paymentPool = match ($status) {
                Sale::STATUS_PENDING  => $paymentPending,
                Sale::STATUS_REJECTED => $paymentRejected,
                default               => $paymentCompleted,
            };

            $paymentType = Arr::random($paymentPool);
            $itemCount   = $this->faker->numberBetween(1, 5);
            $items       = [];
            $totalAmount = 0.0;

            for ($j = 0; $j < $itemCount; $j++) {
                $productId = Arr::random($this->productIds);
                $quantity  = $this->faker->numberBetween(1, 5);
                $stockId   = Arr::random($stockIds);

                $basePrice = $this->productPrices[$productId] ?? $this->faker->numberBetween(10_000, 200_000);
                $price     = round(max(1000, $basePrice * $this->faker->numberBetween(85, 120) / 100), 2);
                $lineTotal = round($price * $quantity, 2);
                $totalAmount += $lineTotal;

                $items[] = [
                    'sale_id'         => null,
                    'product_id'      => $productId,
                    'stock_id'        => $stockId,
                    'product_size_id' => null,
                    'quantity'        => $quantity,
                    'price'           => $price,
                    'total'           => $lineTotal,
                    'created_at'      => $saleDate,
                    'updated_at'      => $saleDate,
                ];
            }

            $totalAmount = round($totalAmount, 2);

            [$paidAmount, $remainingAmount, $mixedCard, $mixedCash] = $this->resolveSalePaymentBreakdown(
                $paymentType,
                $totalAmount
            );

            if ($status === Sale::STATUS_REJECTED) {
                $paidAmount      = 0.0;
                $remainingAmount = 0.0;
                $mixedCard       = 0.0;
                $mixedCash       = 0.0;
            }

            if ($status === Sale::STATUS_PENDING && $paymentType === 'preorder') {
                $paidAmount      = 0.0;
                $remainingAmount = $totalAmount;
            }

            $sale = Sale::query()->create([
                'store_id'          => $storeId,
                'cart_id'           => $cartCounter,
                'client_id'         => $this->randomNullable($this->clientIds, 0.2),
                'total_amount'      => $totalAmount,
                'paid_amount'       => $paidAmount,
                'remaining_amount'  => $remainingAmount,
                'payment_type'      => $paymentType,
                'mixed_cash_amount' => $mixedCash,
                'mixed_card_amount' => $mixedCard,
                'status'            => $status,
                'created_by'        => $createdBy,
                'created_at'        => $saleDate,
                'updated_at'        => $saleDate,
            ]);

            foreach ($items as &$item) {
                $item['sale_id'] = $sale->id;
            }

            SaleItem::query()->insert($items);

            if ($i && $i % 1000 === 0) {
                $this->command?->info(sprintf('   → Generated %d / %d sales...', $i, $needed));
            }
        }
    }

    private function seedPurchases(): void
    {
        $target = 500;
        $needed = max(0, $target - Purchase::count());

        if (
            $needed <= 0 ||
            empty($this->supplierIds) ||
            empty($this->storeIds) ||
            empty($this->productIds)
        ) {
            return;
        }

        $paymentTypes = ['cash', 'card', 'debt', 'partial'];

        for ($i = 0; $i < $needed; $i++) {
            $storeId  = Arr::random($this->storeIds);
            $stockIds = $this->storeStocks[$storeId] ?? $this->stockIds;

            if (empty($stockIds)) {
                $stockIds = $this->stockIds;
            }

            if (empty($stockIds)) {
                continue;
            }

            $stockId        = Arr::random($stockIds);
            $userCandidates = $this->storeUsers[$storeId] ?? $this->userIds;
            if (empty($userCandidates)) {
                $userCandidates = $this->userIds;
            }

            if (empty($userCandidates) || empty($this->supplierIds)) {
                continue;
            }

            $createdBy    = Arr::random($userCandidates);
            $supplierId   = Arr::random($this->supplierIds);
            $purchaseDate = Carbon::instance($this->faker->dateTimeBetween('-18 months', 'now'));
            $paymentType  = Arr::random($paymentTypes);

            $itemCount   = $this->faker->numberBetween(1, 6);
            $items       = [];
            $totalAmount = 0.0;

            for ($j = 0; $j < $itemCount; $j++) {
                $productId = Arr::random($this->productIds);
                $quantity  = $this->faker->numberBetween(5, 200);
                $baseCost  = $this->productInitialPrices[$productId] ?? $this->faker->numberBetween(5_000, 120_000);
                $unitCost  = round(max(1000, $baseCost * $this->faker->numberBetween(90, 120) / 100), 2);
                $lineTotal = round($unitCost * $quantity, 2);
                $totalAmount += $lineTotal;

                $items[] = [
                    'purchase_id'     => null,
                    'product_id'      => $productId,
                    'product_size_id' => null,
                    'stock_id'        => $stockId,
                    'quantity'        => $quantity,
                    'unit_cost'       => $unitCost,
                    'total_cost'      => $lineTotal,
                    'created_at'      => $purchaseDate,
                    'updated_at'      => $purchaseDate,
                ];
            }

            $totalAmount                    = round($totalAmount, 2);
            [$paidAmount, $remainingAmount] = $this->resolvePurchasePaymentBreakdown($paymentType, $totalAmount);

            $purchase = Purchase::query()->create([
                'supplier_id'      => $supplierId,
                'store_id'         => $storeId,
                'stock_id'         => $stockId,
                'created_by'       => $createdBy,
                'purchase_date'    => $purchaseDate,
                'payment_type'     => $paymentType,
                'total_amount'     => $totalAmount,
                'paid_amount'      => $paidAmount,
                'remaining_amount' => $remainingAmount,
                'note'             => $this->faker->optional(0.3)->sentence(),
                'created_at'       => $purchaseDate,
                'updated_at'       => $purchaseDate,
            ]);

            foreach ($items as &$item) {
                $item['purchase_id'] = $purchase->id;
            }

            PurchaseItem::query()->insert($items);

            foreach ($items as $item) {
                ProductStock::query()
                    ->where('product_id', $item['product_id'])
                    ->whereNull('product_size_id')
                    ->where('stock_id', $stockId)
                    ->increment('quantity', $item['quantity']);
            }

            if ($i && $i % 100 === 0) {
                $this->command?->info(sprintf('   → Generated %d / %d purchases...', $i, $needed));
            }
        }
    }

    private function seedExpenses(): void
    {
        $target = 500;
        $needed = max(0, $target - Expense::count());

        if ($needed <= 0 || empty($this->storeIds) || empty($this->userIds)) {
            return;
        }

        $batch = [];
        for ($i = 0; $i < $needed; $i++) {
            $storeId        = Arr::random($this->storeIds);
            $userCandidates = $this->storeUsers[$storeId] ?? $this->userIds;
            if (empty($userCandidates)) {
                $userCandidates = $this->userIds;
            }

            if (empty($userCandidates)) {
                continue;
            }

            $createdBy = Arr::random($userCandidates);
            $date      = Carbon::instance($this->faker->dateTimeBetween('-12 months', 'now'));

            $batch[] = [
                'note'       => $this->faker->sentence(),
                'amount'     => round($this->faker->randomFloat(2, 50_000, 3_000_000), 2),
                'store_id'   => $storeId,
                'created_by' => $createdBy,
                'date'       => $date,
                'created_at' => $date,
                'updated_at' => $date,
            ];

            if (count($batch) === 100) {
                Expense::query()->insert($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            Expense::query()->insert($batch);
        }
    }

    private function resolveSalePaymentBreakdown(string $paymentType, float $totalAmount): array
    {
        $paidAmount      = 0.0;
        $remainingAmount = $totalAmount;
        $mixedCard       = 0.0;
        $mixedCash       = 0.0;

        switch ($paymentType) {
            case 'cash':
            case 'card':
            case 'transfer':
                $paidAmount      = $totalAmount;
                $remainingAmount = 0.0;
                break;
            case 'debt':
                $paidAmount      = 0.0;
                $remainingAmount = $totalAmount;
                break;
            case 'partial':
                $paidAmount      = round($totalAmount * $this->faker->numberBetween(30, 70) / 100, 2);
                $remainingAmount = round($totalAmount - $paidAmount, 2);
                break;
            case 'mixed':
                $mixedCard       = round($totalAmount * $this->faker->numberBetween(30, 70) / 100, 2);
                $mixedCash       = round($totalAmount - $mixedCard, 2);
                $paidAmount      = round($mixedCard + $mixedCash, 2);
                $remainingAmount = 0.0;
                break;
            default:
                $paidAmount      = 0.0;
                $remainingAmount = $totalAmount;
                break;
        }

        if ($remainingAmount < 0) {
            $remainingAmount = 0.0;
        }

        return [$paidAmount, $remainingAmount, $mixedCard, $mixedCash];
    }

    private function resolvePurchasePaymentBreakdown(string $paymentType, float $totalAmount): array
    {
        $paidAmount      = 0.0;
        $remainingAmount = $totalAmount;

        switch ($paymentType) {
            case 'cash':
            case 'card':
                $paidAmount      = $totalAmount;
                $remainingAmount = 0.0;
                break;
            case 'debt':
                $paidAmount      = 0.0;
                $remainingAmount = $totalAmount;
                break;
            case 'partial':
                $paidAmount      = round($totalAmount * $this->faker->numberBetween(40, 80) / 100, 2);
                $remainingAmount = round($totalAmount - $paidAmount, 2);
                break;
        }

        if ($remainingAmount < 0) {
            $remainingAmount = 0.0;
        }

        return [$paidAmount, $remainingAmount];
    }

    private function weightedRandom(array $weights): string
    {
        $total = array_sum($weights);

        if ($total <= 0) {
            return array_key_first($weights);
        }

        $random = random_int(1, $total);

        foreach ($weights as $value => $weight) {
            $random -= $weight;
            if ($random <= 0) {
                return $value;
            }
        }

        return array_key_last($weights);
    }

    private function randomNullable(array $items, float $nullProbability = 0.1): ?int
    {
        if (empty($items)) {
            return null;
        }

        $chance = (int) round($nullProbability * 100);

        if ($chance > 0 && $this->faker->boolean($chance)) {
            return null;
        }

        $value = Arr::random($items);

        return is_array($value) ? Arr::random($value) : $value;
    }
}
