<?php

namespace App\Console\Commands;

use App\Services\SmsService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendDebtorSms extends Command
{
    protected $signature = 'debtors:send-sms';
    protected $description = 'Qarzdorlik muddati o‘tganlarga har 5 kunda SMS yuborish';

    public function handle()
    {
        $today = Carbon::today();

        DB::table('debtors as d')
            ->where(function ($query) {
                $query->where('d.currency', 'uzs')
                    ->where('d.amount', '>', 10000)
                    ->orWhere(function ($q) {
                        $q->where('d.currency', '!=', 'uzs')
                            ->where('d.amount', '>', 0);
                    });
            })
            ->orderBy('d.id')
            ->select('d.id', 'd.full_name', 'd.phone', 'd.amount', 'd.currency', 'd.date')
            ->chunk(100, function ($debtors) use ($today) {
                $smsService = new SmsService();

                foreach ($debtors as $debtor) {
                    // 1. Telefon raqam 12 xonali (998XXXXXXXXX) bo‘lishini tekshir
                    $phone = $this->sanitizePhone($debtor->phone);
                    if (strlen($phone) !== 12) {
                        $this->warn("Telefon raqam noto‘g‘ri formatda: {$debtor->phone} (id:{$debtor->id})");
                        continue;
                    }
                    $debtDate = Carbon::parse($debtor->date);
                    $daysSinceDebt = $debtDate->diffInDays($today);

                    // 2. Qarzdorlik kamida 15 kun bo‘lishi kerak
                    if ($daysSinceDebt < 15) continue;

                    // 3. Oxirgi SMS yuborilgan vaqtni tekshir
                    $lastSms = DB::table('debtor_sms_logs')
                        ->where('debtor_id', $debtor->id)
                        ->orderBy('sent_at', 'desc')
                        ->first();

                    $shouldSend = false;

                    if (!$lastSms) {
                        // Birinchi SMS 15-kuni yoki oshgan bo‘lsa yuboriladi
                        if ($daysSinceDebt >= 15) $shouldSend = true;
                    } else {
                        $lastSent = Carbon::parse($lastSms->sent_at);
                        // Har 5 kun o‘tganda yana yuboriladi
                        if ($lastSent->diffInDays($today) >= 5) $shouldSend = true;
                    }

                    if ($shouldSend) {
                        $message = "Sizda Qumtepada joylashgan Million parfume do'konidan {$debtor->amount} {$debtor->currency} qarzdorlik mavjud. Tez orada to'lang yoki +998903291187 raqamiga murojaat qiling.";
                        $result = $smsService->sendSms($phone, $message);

                        if ($result['success']) {
                            $this->info("SMS yuborildi: {$phone} ({$debtor->full_name})");

                            // SMS logga yoziladi
                            DB::table('debtor_sms_logs')->insert([
                                'debtor_id' => $debtor->id,
                                'sent_at' => now(),
                            ]);
                        } else {
                            Log::error("SMS yuborilmadi: {$phone}. Sabab: {$result['error']}");
                            $this->error("SMS yuborilmadi: {$phone}. Sabab: {$result['error']}");
                        }
                    }
                }
            });
    }

    protected function sanitizePhone($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($phone) == 9) {
            $phone = '998' . $phone;
        }
        return $phone;
    }
}
