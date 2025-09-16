<?php

namespace App\Filament\Pages;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Stock;
use Filament\Forms;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;

class MoveProduct extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationLabel = 'Stockdan Stockga ko‘chirish';
    protected static ?string $title = 'Stock ko‘chirish';

    protected string $view = 'filament.pages.move-product';

    public ?array $data = [];

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('from_stock_id')
                    ->label('Qayerdan')
                    ->options(Stock::scopes('active')->pluck('name', 'id'))
                    ->required(),

                Forms\Components\Select::make('to_stock_id')
                    ->label('Qayerga')
                    ->options(Stock::scopes('active')->pluck('name', 'id'))
                    ->required(),

                Forms\Components\Repeater::make('products')
                    ->label('Mahsulotlar')
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->label('Mahsulot')
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search) {
                                return Product::query()
                                    ->where('name', 'ilike', "%{$search}%")
                                    ->orWhere('barcode', 'ilike', "%{$search}%")
                                    ->limit(50)
                                    ->pluck('name', 'id');
                            })
                            ->getOptionLabelUsing(fn ($value): ?string => Product::find($value)?->name)
                            ->required()
                            ->autofocus(),

                        Forms\Components\TextInput::make('quantity')
                            ->label('Miqdor')
                            ->numeric()
                            ->required(),
                    ])
                    ->minItems(1)
                    ->createItemButtonLabel('Mahsulot qo‘shish'),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        foreach ($data['products'] as $item) {
            // ❌ Agar from_stock da yetarli bo‘lmasa ham decrement qiladi (hohlsangiz validatsiya qo‘shamiz)
            ProductStock::where('product_id', $item['product_id'])
                ->where('stock_id', $data['from_stock_id'])
                ->decrement('quantity', $item['quantity']);

            ProductStock::updateOrCreate(
                [
                    'product_id' => $item['product_id'],
                    'stock_id'   => $data['to_stock_id'],
                ],
                [
                    'quantity' => \DB::raw('quantity + ' . (int) $item['quantity']),
                ]
            );
        }

        Notification::make()
            ->title('Muvaffaqiyatli')
            ->body('Mahsulotlar stocklar orasida ko‘chirildi.')
            ->success()
            ->send();

        $this->form->fill(); // formani tozalash
    }
}
