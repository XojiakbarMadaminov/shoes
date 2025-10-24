<?php

namespace App\Services;

use Throwable;
use App\Models\Sale;
use Telegram\Bot\Api;
use App\Models\TelegramSetting;
use Illuminate\Support\Facades\Log;

class TelegramSaleNotifier
{
    public function notify(Sale $sale, string $event = 'created'): void
    {
        $settings = TelegramSetting::query()->first();

        $botToken = $settings?->bot_token ?: config('services.telegram.bot_token');
        $chatId   = $settings?->sales_chat_id ?: config('services.telegram.sales_chat_id');

        if (blank($botToken) || blank($chatId)) {
            Log::warning('TelegramSaleNotifier missing configuration', [
                'sale_id'   => $sale->id,
                'bot_token' => blank($botToken) ? 'empty' : 'set',
                'chat_id'   => blank($chatId) ? 'empty' : 'set',
            ]);

            return;
        }

        try {
            $telegram = new Api($botToken);
            $message  = $this->buildMessage($sale, $event);

            if (blank($message)) {
                return;
            }

            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text'    => $message,
            ]);
        } catch (Throwable $exception) {
            Log::error('Failed to send sale notification to Telegram', [
                'sale_id' => $sale->id,
                'error'   => $exception->getMessage(),
            ]);
        }
    }

    private function buildMessage(Sale $sale, string $event): string
    {
        $sale->loadMissing(['client', 'items.product', 'store', 'createdBy']);

        $title       = $this->messageTitle($event);
        $storeName   = $sale->store?->name ?? "Noma'lum do'kon";
        $clientName  = $sale->client?->full_name ?? 'Tanlanmagan mijoz';
        $cashierName = $sale->createdBy?->name ?? "Noma'lum kassir";
        $total       = number_format((float) $sale->total_amount, 2, '.', ' ');
        $currency    = $this->resolveCurrency($sale);
        $payment     = $this->paymentTypeLabel($sale->payment_type);
        $date        = optional($sale->created_at)->format('d.m.Y H:i') ?? now()->format('d.m.Y H:i');

        $itemLines = $sale->items
            ->map(function ($item) {
                $name     = $item->product?->name ?? 'Mahsulot';
                $quantity = $item->quantity ?? 0;

                return sprintf('- %s x %s', $name, $quantity);
            })
            ->filter()
            ->implode(PHP_EOL);

        if (blank($itemLines)) {
            $itemLines = '- Mahsulotlar mavjud emas';
        }

        $lines = [
            $title,
            "#️⃣ Sotuv ID: {$sale->id}",
            "🏪 Do'kon: {$storeName}",
            "👤 Mijoz: {$clientName}",
            "🧑‍💼 Kassir: {$cashierName}",
            "💰 Summasi: {$total} {$currency}",
            "💳 To'lov turi: {$payment}",
            '📦 Mahsulotlar:',
            $itemLines,
        ];

        if ($sale->payment_type === 'partial' || (float) $sale->paid_amount > 0) {
            $paid    = number_format((float) $sale->paid_amount, 2, '.', ' ');
            $lines[] = "💵 To'langan: {$paid} {$currency}";
        }

        if ((float) $sale->remaining_amount > 0) {
            $remaining = number_format((float) $sale->remaining_amount, 2, '.', ' ');
            $lines[]   = "📉 Qolgan qarz: {$remaining} {$currency}";
        }

        $lines[] = "⏰ Sana: {$date}";

        return implode(PHP_EOL, array_filter($lines));
    }

    private function paymentTypeLabel(?string $type): string
    {
        return match ($type) {
            'cash'     => 'Naqd',
            'card'     => 'Karta',
            'debt'     => 'Qarz',
            'partial'  => 'Qisman',
            'mixed'    => 'Karta + Naqd',
            'transfer' => "O'tkazma",
            'preorder' => 'Oldindan buyurtma',
            default    => "Noma'lum",
        };
    }

    private function resolveCurrency(Sale $sale): string
    {
        return $sale->store?->currency ?? "so'm";
    }

    private function messageTitle(string $event): string
    {
        return match ($event) {
            'completed' => '✅ Oldindan buyurtma yakunlandi!',
            'canceled'  => '⚠️ Oldindan buyurtma qilindi!',
            default     => '🧾 Yangi sotuv!',
        };
    }
}
