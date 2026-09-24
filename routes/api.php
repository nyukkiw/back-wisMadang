<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CheckOutController;

Route::prefix('v1')->group(function () {
    
    
    Route::get('/keranjang', [CartController::class, 'ambilIsiKeranjang']);
    Route::post('/keranjang/simpan', [CartController::class, 'simpanKeKeranjang']);
    Route::post('/pesanan/checkout', [CheckoutController::class, 'prosesCheckout']);    
});


// Route::get('/test-koneksi', function () {
//     return response()->json([
//         'pesan' => 'Halo Tim! Koneksi dari Laravel ke Next.js berhasil 🚀',
//         'status' => 'Aman Jaya'
//     ]);
// });
