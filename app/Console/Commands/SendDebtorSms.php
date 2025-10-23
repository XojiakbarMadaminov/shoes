<?php

namespace App\Console\Commands;

use App\Services\SmsService;
use Illuminate\Support\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Query\Builder;

class SendDebtorSms extends Command
{
    protected $signature   = 'debtors:send-sms';
    protected $description = 'Qarzdorlik muddati o‘tganlarga har 5 kunda SMS yuborish';

    public function handle(): void
    {
        $this->getEligibleDebtors()->chunk(100, fn ($debtors) => $this->processDebtors($debtors));
    }

    private function processDebtors($debtors): void
    {
        $smsService = new SmsService;

        foreach ($debtors as $debtor) {
            $phone = $this->sanitizePhone($debtor->phone);
            if (strlen($phone) !== 12) {
                $this->warn("Telefon raqam noto‘g‘ri formatda: {$debtor->phone} (id:{$debtor->id})");
                continue;
            }

            if (!$this->shouldSendSms($debtor)) {
                continue;
            }

            //            $message = "Sizda {$debtor->store_address}da joylashgan {$debtor->store_name} do'konidan {$debtor->amount} {$debtor->currency} qarzdorlik mavjud. Tez orada to'lang yoki {$debtor->store_phone} raqamiga murojaat qiling.";
            $message = 'Bu Eskiz dan test';
            $result  = $smsService->sendSms($phone, $message);

            if ($result['success']) {
                DB::table('debtor_sms_logs')->insert([
                    'debtor_id' => $debtor->id,
                    'sent_at'   => now(),
                ]);

                $this->info("SMS yuborildi: {$phone} ({$debtor->full_name})");
            } else {
                Log::error("SMS yuborilmadi: {$phone}. Sabab: {$result['error']}");
                $this->error("SMS yuborilmadi: {$phone}. Sabab: {$result['error']}");
            }
        }
    }

    private function getEligibleDebtors(): Builder
    {
        return DB::table('debtors as d')
            ->leftJoin('clients as c', 'd.client_id', '=', 'c.id')
            ->leftJoin('stores as s', 'd.store_id', '=', 's.id')
            ->where('s.send_sms', true)
            ->where('c.send_sms', true)
            ->where(function ($query) {
                $query->where(fn ($q) => $q->where('d.currency', 'uzs')->where('d.amount', '>', 10000))
                    ->orWhere(fn ($q) => $q->where('d.currency', '!=', 'uzs')->where('d.amount', '>', 0));
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
            );
    }

    private function shouldSendSms($debtor): bool
    {
        $today         = Carbon::today();
        $debtDate      = Carbon::parse($debtor->date);
        $daysSinceDebt = $debtDate->diffInDays($today);

        if ($daysSinceDebt < $debtor->send_sms_interval) {
            return false;
        }

        $lastSms = DB::table('debtor_sms_logs')
            ->where('debtor_id', $debtor->id)
            ->orderByDesc('sent_at')
            ->first();

        if (!$lastSms) {
            return true;
        }

        $lastSent = Carbon::parse($lastSms->sent_at);

        return $lastSent->diffInDays($today) >= $debtor->send_sms_interval;
    }

    protected function sanitizePhone($phone): array|string|null
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($phone) == 9) {
            $phone = '998' . $phone;
        }

        return $phone;
    }
}
