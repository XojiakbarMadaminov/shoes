<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TelegramDailySummaryService;

class SendDailyTelegramSummary extends Command
{
    protected $signature   = 'telegram:send-daily-summary';
    protected $description = 'Kunlik moliyaviy hisobotni Telegram guruhiga yuboradi';

    public function __construct(private TelegramDailySummaryService $summaryService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $date    = now('Asia/Tashkent');
        $success = $this->summaryService->sendForDate($date);

        if ($success) {
            $this->info("Kunlik hisobot Telegram'ga yuborildi ({$date->format('d.m.Y')}).");

            return self::SUCCESS;
        }

        $this->warn("Kunlik Telegram hisobotini yuborib bo'lmadi. Log faylini tekshiring.");

        return self::FAILURE;
    }
}
