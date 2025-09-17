<?php

namespace App\Filament\Pages;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Stock;
use Filament\Forms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
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
                Section::make()
                    ->columns()
                    ->schema([
                        Select::make('from_stock_id')
                            ->label('Qayerdan')
                            ->options(Stock::scopes('active')->pluck('name', 'id'))
                            ->required(),

                        Select::make('to_stock_id')
                            ->label('Qayerga')
                            ->options(Stock::scopes('active')->pluck('name', 'id'))
                            ->required(),
                    ])->columnSpanFull(),

                Section::make()
                    ->columns()
                    ->schema([
                        Repeater::make('products')
                            ->label('Mahsulotlar')
                            ->schema([
                                Select::make('product_id')
                                    ->label('Mahsulot')
                                    ->searchable()
                                    ->getSearchResultsUsing(function (string $search) {
                                        return Product::query()
                                            ->where('name', 'ilike', "%{$search}%")
                                            ->orWhere('barcode', 'ilike', "%{$search}%")
                                            ->limit(50)
                                            ->pluck('name', 'id');
                                    })
                                    ->getOptionLabelUsing(fn($value): ?string => Product::find($value)?->name)
                                    ->required()
                                    ->autofocus(),

                                TextInput::make('quantity')
                                    ->label('Miqdor')
                                    ->numeric()
                                    ->required(),
                            ])
                            ->grid()
                            ->minItems(1)
                            ->addActionLabel('Mahsulot qo‘shish')
                            ->columnSpanFull(),
                    ])->columnSpanFull(),
            ])
            ->columns()
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        $productIds = collect($data['products'])->pluck('product_id')->all();

        // 1) Product nomlarini oldindan olib kelamiz
        $products = \App\Models\Product::whereIn('id', $productIds)
            ->pluck('name', 'id');

        // 2) From_stock dagi mavjud quantitylarni oldindan olib kelamiz
        $fromStockQuantities = \App\Models\ProductStock::whereIn('product_id', $productIds)
            ->where('stock_id', $data['from_stock_id'])
            ->pluck('quantity', 'product_id');

        // 3) Validatsiya
        foreach ($data['products'] as $item) {
            $productName = $products[$item['product_id']] ?? 'Noma’lum mahsulot';
            $currentQty  = $fromStockQuantities[$item['product_id']] ?? 0;

            if ($item['quantity'] > $currentQty) {
                \Filament\Notifications\Notification::make()
                    ->title('Xatolik')
                    ->body("{$productName} uchun maksimal {$currentQty} dona ko‘chirishingiz mumkin.")
                    ->danger()
                    ->send();

                return; // ❌ to‘xtatamiz
            }
        }

        // 4) Ko‘chirish
        foreach ($data['products'] as $item) {
            \App\Models\ProductStock::where('product_id', $item['product_id'])
                ->where('stock_id', $data['from_stock_id'])
                ->decrement('quantity', $item['quantity']);

            \App\Models\ProductStock::updateOrCreate(
                [
                    'product_id' => $item['product_id'],
                    'stock_id'   => $data['to_stock_id'],
                ],
                [
                    'quantity' => \DB::raw('quantity + ' . (int) $item['quantity']),
                ]
            );
        }

        \Filament\Notifications\Notification::make()
            ->title('Muvaffaqiyatli')
            ->body('Mahsulotlar stocklar orasida ko‘chirildi.')
            ->success()
            ->send();

        $this->form->fill();
    }


}
