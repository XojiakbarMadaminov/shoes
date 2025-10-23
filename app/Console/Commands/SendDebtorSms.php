<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Services\SmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendDebtorSms extends Command
{
    protected $signature   = 'debtors:send-sms';
    protected $description = 'Qarzdorlik muddati o‘tganlarga har 5 kunda SMS yuborish';

    public function handle()
    {
        $today = Carbon::today();

        DB::table('debtors as d')
            ->leftJoin('clients as c', 'd.client_id', '=', 'c.id')
            ->leftJoin('stores as s', 'd.store_id', '=', 's.id')
            ->where('s.send_sms', true)
            ->where('c.send_sms', true)
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->where('d.currency', 'uzs')
                        ->where('d.amount', '>', 10000);
                })
                    ->orWhere(function ($q) {
                        $q->where('d.currency', '!=', 'uzs')
                            ->where('d.amount', '>', 0);
                    });
            })
            ->orderBy('d.id')
            ->select(
                'd.id',
                'c.full_name',
                'c.phone',
                'd.amount',
                'd.currency',
                'd.date',
                'd.store_id',
                'c.send_sms_interval',
                's.address as store_address',
                's.phone as store_phone',
                's.name as store_name'
            )
            ->chunk(100, function ($debtors) use ($today) {
                $smsService = new SmsService;

                foreach ($debtors as $debtor) {
                    // 1. Telefon raqam 12 xonali (998XXXXXXXXX) bo‘lishini tekshir
                    $phone = $this->sanitizePhone($debtor->phone);
                    if (strlen($phone) !== 12) {
                        $this->warn("Telefon raqam noto‘g‘ri formatda: {$debtor->phone} (id:{$debtor->id})");
                        continue;
                    }
                    $debtDate      = Carbon::parse($debtor->date);
                    $daysSinceDebt = $debtDate->diffInDays($today);

                    // 2. Qarzdorlik kamida 15 kun bo‘lishi kerak
                    if ($daysSinceDebt < $debtor->send_sms_interval) {
                        continue;
                    }

                    // 3. Oxirgi SMS yuborilgan vaqtni tekshir
                    $lastSms = DB::table('debtor_sms_logs')
                        ->where('debtor_id', $debtor->id)
                        ->orderBy('sent_at', 'desc')
                        ->first();

                    $shouldSend = false;

                    if (!$lastSms) {
                        if ($daysSinceDebt >= $debtor->send_sms_interval) {
                            $shouldSend = true;
                        }
                    } else {
                        $lastSent = Carbon::parse($lastSms->sent_at);
                        // Har 5 kun o‘tganda yana yuboriladi
                        if ($lastSent->diffInDays($today) >= 5) {
                            $shouldSend = true;
                        }
                    }

                    if ($shouldSend) {
                        $message = "Sizda {$debtor->store_address}da joylashgan {$debtor->store_name} do'konidan {$debtor->amount} {$debtor->currency} qarzdorlik mavjud. Tez orada to'lang yoki {$debtor->store_phone} raqamiga murojaat qiling.";
                        $this->info($message);

                        $result = $smsService->sendSms($phone, $message);

                        if ($result['success']) {
                            $this->info("SMS yuborildi: {$phone} ({$debtor->full_name})");

                            // SMS logga yoziladi
                            DB::table('debtor_sms_logs')->insert([
                                'debtor_id' => $debtor->id,
                                'sent_at'   => now(),
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
