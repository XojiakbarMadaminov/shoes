<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductSize;
use App\Models\ProductStock;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\SupplierDebt;
use App\Models\SupplierDebtTransaction;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseService
{
    /**
     * @throws \Throwable
     */
    public function create(array $data): Purchase
    {
        $user    = Auth::user();
        $storeId = $user?->current_store_id;

        if (!$storeId) {
            throw ValidationException::withMessages([
                'store_id' => 'Foydalanuvchi uchun joriy do‘kon tanlanmagan.',
            ]);
        }

        $itemsData = collect($data['items'] ?? [])
            ->filter(fn ($item) => !empty($item['product_id']))
            ->values();

        if ($itemsData->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Hech bo‘lmaganda bitta mahsulot qo‘shing.',
            ]);
        }

        $stockId = $data['stock_id'] ?? null;
        if (!$stockId) {
            throw ValidationException::withMessages([
                'stock_id' => 'Ombor (sklad) tanlanishi kerak.',
            ]);
        }

        return DB::transaction(function () use ($data, $itemsData, $storeId, $stockId, $user) {
            $purchase = Purchase::create([
                'supplier_id'      => $data['supplier_id'],
                'store_id'         => $storeId,
                'stock_id'         => $stockId,
                'created_by'       => $user?->id,
                'purchase_date'    => $data['purchase_date'],
                'payment_type'     => $data['payment_type'],
                'note'             => $data['note'] ?? null,
            ]);

            $totalAmount = 0;

            foreach ($itemsData as $item) {
                $product = Product::withoutGlobalScope('current_store')->findOrFail($item['product_id']);
                $unit    = (float) ($item['unit_cost'] ?? 0);

                if ($unit <= 0) {
                    throw ValidationException::withMessages([
                        'items' => "Mahsulot {$product->name} uchun xarid narxi 0 dan katta bo‘lishi kerak.",
                    ]);
                }

                if ($product->isPackageBased()) {
                    $quantity = (int) ($item['quantity'] ?? 0);

                    if ($quantity <= 0) {
                        throw ValidationException::withMessages([
                            'items' => "{$product->name} uchun miqdor kiritilmadi.",
                        ]);
                    }

                    $lineTotal    = round($unit * $quantity, 2);
                    $totalAmount += $lineTotal;

                    PurchaseItem::create([
                        'purchase_id'     => $purchase->id,
                        'product_id'      => $product->id,
                        'product_size_id' => null,
                        'stock_id'        => $stockId,
                        'quantity'        => $quantity,
                        'unit_cost'       => $unit,
                        'total_cost'      => $lineTotal,
                    ]);

                    $this->incrementProductStock($product->id, null, $stockId, $quantity);
                } else {
                    $sizes = collect($item['size_quantities'] ?? [])
                        ->map(fn ($row) => [
                            'product_size_id' => Arr::get($row, 'product_size_id'),
                            'quantity'        => (int) Arr::get($row, 'quantity', 0),
                        ])
                        ->filter(fn ($row) => ($row['product_size_id'] ?? null) && $row['quantity'] > 0);

                    if ($sizes->isEmpty()) {
                        throw ValidationException::withMessages([
                            'items' => "{$product->name} uchun razmer bo‘yicha miqdorlar kiritilmadi.",
                        ]);
                    }

                    /** @var ProductSize $size */
                    foreach ($sizes as $sizeRow) {
                        $size = ProductSize::where('product_id', $product->id)
                            ->where('id', $sizeRow['product_size_id'])
                            ->first();

                        if (!$size) {
                            throw ValidationException::withMessages([
                                'items' => "{$product->name} uchun razmer topilmadi.",
                            ]);
                        }

                        $quantity = $sizeRow['quantity'];
                        $lineTotal = round($unit * $quantity, 2);
                        $totalAmount += $lineTotal;

                        PurchaseItem::create([
                            'purchase_id'     => $purchase->id,
                            'product_id'      => $product->id,
                            'product_size_id' => $size->id,
                            'stock_id'        => $stockId,
                            'quantity'        => $quantity,
                            'unit_cost'       => $unit,
                            'total_cost'      => $lineTotal,
                        ]);

                        $this->incrementProductStock(null, $size->id, $stockId, $quantity);
                    }
                }
            }

            $purchase->update([
                'total_amount'     => $totalAmount,
                'paid_amount'      => $paid = $this->resolvePaidAmount($data['payment_type'], $totalAmount, $data['partial_paid_amount'] ?? null),
                'remaining_amount' => max(0, round($totalAmount - $paid, 2)),
            ]);

            if ($purchase->remaining_amount > 0) {
                $debt = SupplierDebt::firstOrCreate(
                    [
                        'supplier_id' => $purchase->supplier_id,
                        'store_id'    => $storeId,
                    ],
                    [
                        'amount'   => 0,
                        'currency' => 'uzs',
                    ]
                );

                $debt->increment('amount', $purchase->remaining_amount);

                SupplierDebtTransaction::create([
                    'supplier_debt_id' => $debt->id,
                    'purchase_id'      => $purchase->id,
                    'type'             => 'debt',
                    'amount'           => $purchase->remaining_amount,
                    'date'             => Carbon::parse($purchase->purchase_date)->endOfDay(),
                    'note'             => "Purchase #{$purchase->id}",
                ]);
            }

            return $purchase->fresh(['supplier', 'items.product', 'items.productSize']);
        });
    }

    protected function resolvePaidAmount(string $paymentType, float $total, ?float $partial = null): float
    {
        return match ($paymentType) {
            'cash', 'card' => $total,
            'debt'         => 0.0,
            'partial'      => $this->validatePartialAmount($partial, $total),
            default        => $total,
        };
    }

    protected function validatePartialAmount(?float $amount, float $total): float
    {
        $amount = round((float) ($amount ?? 0), 2);

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'partial_paid_amount' => 'Qisman to‘lov summasi kiritilishi kerak.',
            ]);
        }

        if ($amount >= $total) {
            throw ValidationException::withMessages([
                'partial_paid_amount' => 'Qisman to‘lov jami summadan kichik bo‘lishi kerak.',
            ]);
        }

        return $amount;
    }

    protected function incrementProductStock(?int $productId, ?int $productSizeId, int $stockId, int $quantity): void
    {
        $record = ProductStock::firstOrNew([
            'product_id'      => $productId,
            'product_size_id' => $productSizeId,
            'stock_id'        => $stockId,
        ]);

        $record->quantity = (int) $record->quantity + $quantity;
        $record->save();
    }
}
