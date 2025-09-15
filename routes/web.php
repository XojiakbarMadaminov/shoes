<?php

use App\Models\Debtor;
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
