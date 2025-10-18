<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    // Triggered when clicking stock quantity on size-based products
    public function sizes_breakdown($record): void
    {
        // Mount the registered table record action to show the modal
        $this->mountTableAction('sizes_breakdown', $record);
    }
}
