<?php

namespace App\Support;

use App\Models\Sale;

class ReceiptData
{
    public static function fromSale(Sale $sale, array $metaOverrides = []): array
    {
        $items     = $sale->items()->with(['product', 'productSize'])->get();
        $storeId   = $sale->store_id;
        $cartId    = $sale->cart_id ?? $sale->id;
        $created   = $sale->created_at ?? now();
        $timestamp = $created instanceof \DateTimeInterface
            ? $created->getTimestamp()
            : now()->timestamp;

        $receiptNumber = 'R' . str_pad((string) $cartId, 4, '0', STR_PAD_LEFT) . $timestamp;

        return [
            'cart_id'  => $cartId,
            'store_id' => $storeId,
            'items'    => $items->map(function ($item) {
                return [
                    'name'  => $item->product?->name ?? 'Mahsulot',
                    'size'  => $item->productSize?->size,
                    'qty'   => (float) $item->quantity,
                    'price' => (float) $item->price,
                ];
            })->toArray(),
            'totals' => [
                'qty'    => (float) $items->sum('quantity'),
                'amount' => (float) $sale->total_amount,
            ],
            'date' => $created instanceof \DateTimeInterface
                ? $created->format('d.m.Y H:i:s')
                : now()->format('d.m.Y H:i:s'),
            'receipt_number' => $receiptNumber,
            'meta'           => array_merge([
                'sale_id'          => $sale->id,
                'client_name'      => $sale->client?->full_name ?? '-',
                'cashier_name'     => $sale->createdBy?->name ?? null,
                'payment_type'     => $sale->payment_type,
                'paid_amount'      => $sale->paid_amount,
                'remaining_amount' => $sale->remaining_amount,
            ], $metaOverrides),
        ];
    }
}
