<?php


// nah route harus disini, 
// seperti mengambil data dari database, enkripsi password, proses validasi data, dll.


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;




Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::get('/test-koneksi', function () {
    return response()->json([
        'pesan' => 'Halo Tim! Koneksi dari Laravel ke Next.js berhasil 🚀',
        'status' => 'Aman Jaya'
    ]);
});
