<?php


// nah route harus disini, 
// seperti mengambil data dari database, enkripsi password, proses validasi data, dll.
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CartController;


Route::prefix('v1')->group(function () {
    
    // Cara penulisan standar industri untuk GET dan POST
    Route::get('/keranjang', [CartController::class, 'ambilIsiKeranjang']);
    Route::post('/keranjang/simpan', [CartController::class, 'simpanKeKeranjang']);
    
});


// Route::get('/test-koneksi', function () {
//     return response()->json([
//         'pesan' => 'Halo Tim! Koneksi dari Laravel ke Next.js berhasil 🚀',
//         'status' => 'Aman Jaya'
//     ]);
// });
