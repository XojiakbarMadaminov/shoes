<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\Store;
use Telegram\Bot\Api;
use App\Models\Expense;
use App\Models\Purchase;
use App\Models\SaleItem;
use Illuminate\Support\Carbon;
use App\Models\TelegramSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class TelegramDailySummaryService
{
    public function sendForDate(Carbon $date): bool
    {
        [$botToken, $chatId] = $this->resolveCredentials();

        if (blank($botToken) || blank($chatId)) {
            Log::warning('Daily Telegram summary skipped due to missing credentials.');

            return false;
        }

        $periodStart = $date->copy()->startOfDay();
        $periodEnd   = $date->copy()->endOfDay();

        $stores = Store::query()->orderBy('name')->get();

        if ($stores->isEmpty()) {
            Log::warning('Daily Telegram summary skipped because no stores were found.');

            return false;
        }

        $summaries = $stores->map(function (Store $store) use ($periodStart, $periodEnd) {
            return [
                'store'   => $store,
                'metrics' => $this->collectStoreSummary($periodStart, $periodEnd, $store),
            ];
        })->values();

        $message = $this->formatMessage($summaries, $periodStart);

        if (blank($message)) {
            return false;
        }

        try {
            $client = new Api($botToken);
            $client->sendMessage([
                'chat_id' => $chatId,
                'text'    => $message,
            ]);
        } catch (\Throwable $throwable) {
            Log::error('Failed to send daily Telegram summary.', [
                'error' => $throwable->getMessage(),
            ]);

            return false;
        }

        return true;
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    private function resolveCredentials(): array
    {
        $settings = TelegramSetting::query()->first();

        $botToken = $settings?->bot_token ?: config('services.telegram.bot_token');
        $chatId   = $settings?->sales_chat_id ?: config('services.telegram.sales_chat_id');

        return [$botToken, $chatId];
    }

    private function collectStoreSummary(Carbon $start, Carbon $end, Store $store): array
    {
        $saleQuery = Sale::withoutGlobalScopes()
            ->whereBetween('created_at', [$start, $end])
            ->where('status', Sale::STATUS_COMPLETED)
            ->where('store_id', $store->id);

        $totalSales = (clone $saleQuery)->sum('total_amount');

        $debtSales = (clone $saleQuery)
            ->where('payment_type', 'debt')
            ->sum('total_amount');

        $totalProfit = SaleItem::withoutGlobalScopes()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->whereBetween('sales.created_at', [$start, $end])
            ->where('sales.status', Sale::STATUS_COMPLETED)
            ->where('sales.store_id', $store->id)
            ->selectRaw('COALESCE(SUM( (sale_items.price - products.initial_price) * sale_items.quantity ), 0) AS profit')
            ->value('profit');

        $totalExpenses = Expense::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->whereBetween('date', [$start, $end])
            ->sum('amount');

        $purchaseQuery = Purchase::withoutGlobalScopes()
            ->whereBetween('purchase_date', [$start, $end])
            ->where('store_id', $store->id);

        $totalPurchases = (clone $purchaseQuery)->sum('total_amount');

        $debtPurchases = (clone $purchaseQuery)
            ->where('payment_type', 'debt')
            ->sum('total_amount');

        return [
            'total_sales'     => (float) $totalSales,
            'profit'          => (float) ($totalProfit ?? 0),
            'debt_sales'      => (float) $debtSales,
            'expenses'        => (float) $totalExpenses,
            'total_purchases' => (float) $totalPurchases,
            'debt_purchases'  => (float) $debtPurchases,
        ];
    }

    private function formatMessage(Collection $summaries, Carbon $date): string
    {
        $lines = [
            '📊 Kunlik moliyaviy hisobot',
            '📅 Sana: ' . $date->format('d.m.Y'),
            '',
        ];

        foreach ($summaries as $index => $summary) {
            $store    = $summary['store'];
            $metrics  = $summary['metrics'];
            $currency = $store->currency ?? "so'm";

            $lines[] = "🏪 {$store->name}";
            $lines[] = '🛒 Umumiy sotuvlar: ' . $this->formatCurrency($metrics['total_sales'], $currency);
            $lines[] = '💹 Foyda: ' . $this->formatCurrency($metrics['profit'], $currency);
            $lines[] = '📉 Qarzga sotuvlar: ' . $this->formatCurrency($metrics['debt_sales'], $currency);
            $lines[] = '💸 Xarajatlar: ' . $this->formatCurrency($metrics['expenses'], $currency);
            $lines[] = "📦 Ta'minotchidan xaridlar: " . $this->formatCurrency($metrics['total_purchases'], $currency);
            $lines[] = "🤝 Ta'minotchidan qarzga xaridlar: " . $this->formatCurrency($metrics['debt_purchases'], $currency);

            if ($index !== $summaries->count() - 1) {
                $lines[] = '';
            }
        }

        return implode(PHP_EOL, $lines);
    }

    private function formatCurrency(float $value, string $currency): string
    {
        return number_format($value, 0, '.', ' ') . ' ' . $currency;
    }
}
