<?php

use App\Models\Debtor;
use App\Models\Product;
use App\Models\Store;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// debtor uchun
Route::get('/debtor/{debtor}/check-pdf', function (Debtor $debtor) {
    $debtor->load('transactions');

    $base = 300;
    $extra = 20 * $debtor->transactions->count();
    $height = min(396, $base + $extra); // max 140mm

    return Pdf::loadView('debtor-check', compact('debtor'))
        ->setPaper([0, 0, 176, $height], 'portrait')  // 62mm × height
        ->stream('check.pdf');
})->name('debtor.check.pdf');

Route::get('/switch-store/{store}', function (Store $store) {
    $user = auth()->user();

    abort_unless($user->stores->contains($store->id), 403);

    $user->update(['current_store_id' => $store->id]);

    return back();
})->name('switch-store');

// 1. Bitta product uchun
Route::get('/products/{product}/barcode-pdf', function (Product $product) {
    return Pdf::loadView('product-barcode', ['products' => collect([$product])])
//        ->setPaper([0, 0, 85.0, 65.2], 'landscape') // 23mm x 30mm
        ->setOptions(['defaultFont' => 'sans-serif'])
        ->stream("barcode-{$product->id}.pdf");
})->name('product.barcode.pdf');


// 2. Ko‘p product uchun (masalan, tanlanganlar)
Route::get('/products/barcodes/bulk', function () {
    $productIds = request()->input('ids', []); // ?ids[]=1&ids[]=3&ids[]=5
    $products = Product::whereIn('id', $productIds)->get();

    return Pdf::loadView('product-barcode', compact('products'))
        ->setPaper([0, 0, 136, 85.0]) // ko‘proq sahifali variant uchun A4 mos
        ->stream("barcodes.pdf");
})->name('product.barcodes.bulk');
