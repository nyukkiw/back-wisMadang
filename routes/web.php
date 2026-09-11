<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/test-koneksi', function () {
    return response()->json([
        'pesan' => 'Halo Tim! Koneksi dari Laravel ke Next.js berhasil 🚀',
        'status' => 'Aman Jaya'
    ]);
});

// jangan koding disini. untuk backend lakukan koding di api.php