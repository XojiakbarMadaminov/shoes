<?php

use App\Models\Store;
use App\Models\Debtor;
use App\Models\Product;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->to('/admin/login');
});

// debtor uchun
Route::get('/debtor/{debtor}/check-pdf', function (Debtor $debtor) {
    $debtor->load('transactions');

    $base   = 300;
    $extra  = 20 * $debtor->transactions->count();
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
Route::get('/products/{product}/barcode-pdf', function (Product $product, Request $request) {
    $size = $request->get('size', '30x20');

    $sizes = [
        '30x20' => [0, 0, 85.04, 56.69],   // 30mm x 20mm
        '57x30' => [0, 0, 161.62, 85.04],  // 57mm x 30mm
        '85x65' => [0, 0, 240.94, 184.25], // 85mm x 65mm
    ];

    $paper = $sizes[$size] ?? $sizes['30x20'];

    return Pdf::loadView('product-barcode', [
        'products' => collect([$product]),
        'size'     => $size,
    ])
        ->setPaper($paper)
        ->stream("barcode-{$product->id}.pdf");
})->name('product.barcode.pdf');

// 2. Ko‘p product uchun (masalan, tanlanganlar)
Route::get('/products/barcodes/bulk', function (Request $request) {
    $ids  = explode(',', $request->input('ids', ''));
    $size = $request->get('size', '30x20');

    $products = Product::whereIn('id', $ids)->get();

    $sizes = [
        '30x20' => [0, 0, 85.04, 56.69],
        '57x30' => [0, 0, 161.62, 85.04],
        '85x65' => [0, 0, 240.94, 184.25],
    ];

    $paper = $sizes[$size] ?? $sizes['30x20'];

    return Pdf::loadView('product-barcode', [
        'products' => $products,
        'size'     => $size,
    ])
        ->setPaper($paper)
        ->stream('barcodes.pdf');
})->name('product.barcodes.bulk');
