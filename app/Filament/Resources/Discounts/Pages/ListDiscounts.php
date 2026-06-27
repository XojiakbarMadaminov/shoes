<?php

namespace App\Filament\Resources\Discounts\Pages;

use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\Discounts\DiscountResource;

class ListDiscounts extends ListRecords
{
    protected static string $resource = DiscountResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
