<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\IsiKeranjang;
use App\Models\Keranjang;

abstract class Controller
{
    protected function getCardData()
    {
        // 💡 CATATAN: Sementara fitur login teman Anda belum digabung, 
        // kita kunci menggunakan ID pengguna manual = 1 terlebih dahulu untuk pengetesan.
        // Jika nanti token Sanctum sudah aktif, kode di bawah tinggal diganti menjadi: $userId = $request->user()->id;
        $userId = 1;
        // Cari keranjang aktif milik user, beserta rincian menu satuan atau paket kateringnya
        $keranjang = Keranjang::with(['isiKeranjang.menu', 'isiKeranjang.paketCatering'])
            ->where('pengguna_id', $userId)
            ->first();

        // Jika keranjang belum pernah dibuat sama sekali di database, kembalikan array kosong
        if (!$keranjang) {
            return response()->json([
                'success' => true,
                'message' => 'Keranjang masih kosong',
                'data' => []
            ], 200);
        }

        return response()->json([
            'success' => true,
            'message' => 'Berhasil mengambil data keranjang',
            'data' => $keranjang
        ], 200);
    }

}
