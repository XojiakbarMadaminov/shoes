<?php

namespace App\Filament\Resources\SupplierDebts\Pages;

use App\Filament\Resources\SupplierDebts\SupplierDebtResource;
use Filament\Resources\Pages\ListRecords;

class ListSupplierDebts extends ListRecords
{
    protected static string $resource = SupplierDebtResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
