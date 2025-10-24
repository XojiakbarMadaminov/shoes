<?php

namespace App\Observers;

use App\Models\Sale;
use App\Services\TelegramSaleNotifier;

class SaleObserver
{
    public bool $afterCommit = true;

    public function __construct(private TelegramSaleNotifier $notifier) {}

    public function created(Sale $sale): void
    {
        $this->notifier->notify($sale);
    }
}
