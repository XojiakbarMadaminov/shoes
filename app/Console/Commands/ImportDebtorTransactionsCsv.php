<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use SplFileObject;
use App\Models\Store;
use App\Models\Client;
use App\Models\Debtor;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use App\Models\DebtorTransaction;
use Illuminate\Support\Facades\DB;

class ImportDebtorTransactionsCsv extends Command
{
    protected $signature = 'debtors:import-transactions
        {file : Path to the CSV file}
        {--delimiter=, : CSV delimiter}
        {--no-header : Treat file as having no header row}
        {--store-id= : Store id for resolving debtor by phone}
        {--store= : Store name for resolving debtor by phone}
        {--update-existing : If a row matches existing (debtor_id, type, date, amount), skip unless this is set to allow duplicates}
        {--no-balance : Do not adjust debtor amounts, only create transactions}
        {--recalculate : After import, recalculate debtor balances from transactions}
    ';

    protected $description = 'Import debtor transactions from CSV and update debtor balances.';

    public function handle(): int
    {
        $path            = (string) $this->argument('file');
        $delimiter       = (string) $this->option('delimiter');
        $hasHeader       = !(bool) $this->option('no-header');
        $allowDuplicates = (bool) $this->option('update-existing');
        $noBalance       = (bool) $this->option('no-balance');
        $recalculate     = (bool) $this->option('recalculate');

        if (!is_file($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        $defaultStoreId = $this->resolveDefaultStoreId();

        $file = new SplFileObject($path);
        $file->setFlags(SplFileObject::READ_CSV|SplFileObject::SKIP_EMPTY|SplFileObject::DROP_NEW_LINE);
        $file->setCsvControl($delimiter);

        $header    = [];
        $rowNumber = 0;
        $created   = 0;
        $skipped   = 0;
        $errors    = 0;
        $touched   = [];

        if ($hasHeader) {
            $header = $this->readRow($file) ?? [];
            $header = array_map(fn ($h) => strtolower(trim((string) $h)), $header);
        }

        while (!$file->eof()) {
            $rowNumber++;
            $row = $this->readRow($file);
            if ($row === null) {
                break;
            }
            if ($row === []) {
                continue;
            }

            $data = $this->normalizeRow($row, $header, $hasHeader);

            $debtor = $this->resolveDebtor($data, $defaultStoreId);
            if (!$debtor) {
                $skipped++;
                $this->line("[skip] Row {$rowNumber}: debtor not found (provide debtor_id or phone + store)");
                continue;
            }

            $typeRaw = strtolower(trim((string) Arr::get($data, 'transaction_type', Arr::get($data, 'type', ''))));
            if (!in_array($typeRaw, ['debt', 'payment'], true)) {
                $skipped++;
                $this->line("[skip] Row {$rowNumber}: invalid type '{$typeRaw}'");
                continue;
            }

            $amount = $this->toIntNullable(Arr::get($data, 'transaction_amount', Arr::get($data, 'amount')));
            if ($amount === null || $amount <= 0) {
                $skipped++;
                $this->line("[skip] Row {$rowNumber}: missing or non-positive amount");
                continue;
            }

            $dateStr = (string) Arr::get($data, 'transaction_date', Arr::get($data, 'date', ''));
            $date    = $this->parseDate($dateStr) ?? now();
            $note    = trim((string) Arr::get($data, 'transaction_note', Arr::get($data, 'note', '')));
            $saleId  = Arr::get($data, 'sale_id');

            DB::beginTransaction();

            try {
                if (!$allowDuplicates) {
                    $dup = DebtorTransaction::query()
                        ->where('debtor_id', $debtor->id)
                        ->where('type', $typeRaw)
                        ->where('amount', $amount)
                        ->whereDate('date', $date->toDateString())
                        ->first();
                    if ($dup) {
                        DB::rollBack();
                        $skipped++;
                        $this->line("[skip] Row {$rowNumber}: similar transaction exists (same debtor, type, amount, date)");
                        continue;
                    }
                }

                // Lock debtor row, adjust balance, create transaction
                $lockedDebtor = Debtor::query()->whereKey($debtor->id)->lockForUpdate()->first();
                if (!$lockedDebtor) {
                    throw new \RuntimeException('Debtor disappeared during import.');
                }

                if (!$noBalance && !$recalculate) {
                    if ($typeRaw === 'debt') {
                        $lockedDebtor->amount += $amount;
                    } else {
                        $lockedDebtor->amount -= $amount;
                    }
                    $lockedDebtor->save();
                }

                DebtorTransaction::create([
                    'debtor_id' => $lockedDebtor->id,
                    'amount'    => $amount,
                    'type'      => $typeRaw,
                    'date'      => $date,
                    'note'      => $note !== '' ? $note : null,
                    'sale_id'   => $saleId ?: null,
                ]);

                DB::commit();
                $created++;
                $touched[$lockedDebtor->id] = true;
                $this->line("[create] Row {$rowNumber}: debtor #{$lockedDebtor->id} {$typeRaw} {$amount}");
            } catch (\Throwable $e) {
                DB::rollBack();
                $errors++;
                $this->error("[error] Row {$rowNumber}: " . $e->getMessage());
            }
        }

        if ($recalculate && !empty($touched)) {
            $ids = array_keys($touched);
            foreach ($ids as $id) {
                $totals = DebtorTransaction::query()
                    ->selectRaw("SUM(CASE WHEN type = 'debt' THEN amount ELSE 0 END) as sum_debt, SUM(CASE WHEN type = 'payment' THEN amount ELSE 0 END) as sum_payment")
                    ->where('debtor_id', $id)
                    ->first();
                $net = ((int) ($totals->sum_debt ?? 0)) - ((int) ($totals->sum_payment ?? 0));
                Debtor::query()->whereKey($id)->update(['amount' => max(0, $net)]);
            }
            $this->info('Balances recalculated from transactions for touched debtors.');
        }

        $this->info("Done. Created: {$created}, Skipped: {$skipped}, Errors: {$errors}");

        return self::SUCCESS;
    }

    protected function readRow(SplFileObject $file): ?array
    {
        if ($file->eof()) {
            return null;
        }
        $row = $file->fgetcsv();
        if ($row === false) {
            return null;
        }
        if (isset($row[0])) {
            $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $row[0]);
        }
        $row = array_map(fn ($v) => is_null($v) ? '' : trim((string) $v), $row);
        if (count(array_filter($row, fn ($v) => $v !== '')) === 0) {
            return [];
        }

        return $row;
    }

    protected function normalizeRow(array $row, array $header, bool $hasHeader): array
    {
        $data = [];
        if ($hasHeader) {
            foreach ($row as $i => $value) {
                $key        = $header[$i] ?? (string) $i;
                $data[$key] = $value;
            }
        } else {
            // Positional: 0=debtor_id, 1=phone, 2=transaction_type, 3=transaction_amount, 4=transaction_date, 5=transaction_note
            $data['debtor_id']          = $row[0] ?? '';
            $data['phone']              = $row[1] ?? '';
            $data['transaction_type']   = $row[2] ?? '';
            $data['transaction_amount'] = $row[3] ?? '';
            $data['transaction_date']   = $row[4] ?? '';
            $data['transaction_note']   = $row[5] ?? '';
        }

        return $data;
    }

    protected function resolveDebtor(array $data, ?int $defaultStoreId): ?Debtor
    {
        $rowStoreId = $this->resolveRowStoreId($data, $defaultStoreId);
        $phone      = $this->normalizePhone((string) Arr::get($data, 'phone', ''));
        $debtorId   = Arr::get($data, 'debtor_id');

        if ($debtorId) {
            $debtor = Debtor::query()->find((int) $debtorId);
            if (!$debtor) {
                return null;
            }

            // If phone provided, validate it matches debtor's client
            if ($phone !== '') {
                $clientPhone = optional($debtor->client)->phone;
                if ($clientPhone && $this->normalizePhone($clientPhone) !== $phone) {
                    return null; // mismatch, avoid mixing
                }
            }
            // If store provided, validate it matches
            if ($rowStoreId && (int) $debtor->store_id !== (int) $rowStoreId) {
                return null;
            }

            return $debtor;
        }

        if ($phone !== '') {
            $client = Client::query()->where('phone', $phone)->first();
            if (!$client) {
                return null;
            }

            $query = Debtor::query()->where('client_id', $client->id);
            if ($rowStoreId) {
                $query->where('store_id', $rowStoreId);

                return $query->first();
            }

            // No store provided: require unambiguous debtor
            $debtors = $query->get();
            if ($debtors->count() === 1) {
                return $debtors->first();
            }

            return null; // ambiguous across stores
        }

        return null;
    }

    protected function resolveDefaultStoreId(): ?int
    {
        $storeIdOpt = $this->option('store-id');
        if ($storeIdOpt) {
            $store = Store::query()->find((int) $storeIdOpt);
            if (!$store) {
                $this->error("Store not found by id: {$storeIdOpt}");

                return null;
            }

            return (int) $store->id;
        }
        $storeNameOpt = $this->option('store');
        if ($storeNameOpt) {
            $store = Store::query()->where('name', $storeNameOpt)->first();
            if (!$store) {
                $this->error("Store not found by name: {$storeNameOpt}");

                return null;
            }

            return (int) $store->id;
        }
        $stores = Store::query()->get(['id']);
        if ($stores->count() === 1) {
            return (int) $stores->first()->id;
        }

        return null;
    }

    protected function resolveRowStoreId(array $data, ?int $defaultStoreId): ?int
    {
        $storeId = Arr::get($data, 'store_id');
        if ($storeId) {
            return (int) $storeId;
        }
        $storeName = Arr::get($data, 'store');
        if ($storeName) {
            $store = Store::query()->where('name', $storeName)->first();
            if ($store) {
                return (int) $store->id;
            }
        }

        return $defaultStoreId;
    }

    protected function parseDate(string $value): ?Carbon
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function toIntNullable(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        $normalized = preg_replace('/[^0-9\-]/', '', $value);
        if ($normalized === '' || $normalized === '-') {
            return null;
        }

        return (int) $normalized;
    }

    protected function normalizePhone(string $phone): string
    {
        $phone = trim($phone);
        if ($phone === '') {
            return '';
        }
        $digits = preg_replace('/[^0-9\+]/', '', $phone);
        if (Str::startsWith($digits, '++')) {
            $digits = '+' . ltrim($digits, '+');
        }

        return $digits;
    }
}
