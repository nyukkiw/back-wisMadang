<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Keranjang;
use App\Models\IsiKeranjang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\function\allFunction;

class CartController extends Controller 

{
    public function ambilIsiKeranjang(Request $request)
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

    public function simpanKeKeranjang(Request $request)
    {
        // Validasi input data dari frontend / Postman
        $validator = Validator::make($request->json()->all(), [
            'menu_id'  => 'nullable|integer|exists:menu,id',
            'paket_id' => 'nullable|integer|exists:paket_catering,id',
            'jumlah'   => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        // Pastikan salah satu harus diisi (tidak boleh kosong dua-duanya)
        if (!$request->menu_id && !$request->paket_id) {
            return response()->json([
                'success' => false,
                'message' => 'Wajib memilih menu_id atau paket_id yang mau dimasukkan.'
            ], 400);
        }

        // Menggunakan ID pengguna tiruan manual = 1 demi kelancaran tes Postman
        $userId = 1;

        // LANGKAH A: Pastikan wadah "keranjang" user sudah ada di database. Jika belum, buat baru otomatis.
        $keranjang = Keranjang::firstOrCreate(
            ['pengguna_id' => $userId],
            ['diperbarui_pada' => now()]
        );

        // LANGKAH B: Cari apakah makanan/paket ini sudah pernah dimasukkan ke dalam keranjang tersebut
        $itemKondisi = [
            'keranjang_id' => $keranjang->id,
            'menu_id'      => $request->menu_id,
            'paket_id'     => $request->paket_id,
        ];

        $itemKeranjang = IsiKeranjang::where($itemKondisi)->first();

        // LANGKAH C: Eksekusi Logika Aturan Bisnis Wis Madang
        // Kondisi 1: Jika jumlah yang dikirim adalah 0, hapus item tersebut dari database
        if ($request->jumlah == 0) {
            if ($itemKeranjang) {
                $itemKeranjang->delete();
                return response()->json([
                    'success' => true,
                    'message' => 'Item berhasil dihapus dari keranjang belanja'
                ], 200);
            }
            return response()->json([
                'success' => false,
                'message' => 'Item memang tidak ada di dalam keranjang'
            ], 404);
        }

        // Kondisi 2: Jika item SUDAH ADA di database, tambahkan nilainya secara akumulatif
        if ($itemKeranjang) {
            $itemKeranjang->jumlah += $request->jumlah;
            $itemKeranjang->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Porsi kuantitas item berhasil ditambahkan di keranjang',
                'data'    => $itemKeranjang
            ], 200);
        }

        // Kondisi 3: Jika item BELUM ADA di database, lakukan insert baris baru
        $itemBaru = IsiKeranjang::create([
            'keranjang_id' => $keranjang->id,
            'menu_id'      => $request->menu_id,
            'paket_id'     => $request->paket_id,
            'jumlah'       => $request->jumlah,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Item baru berhasil dimasukkan ke keranjang belanja',
            'data'    => $itemBaru
        ], 201);
    }
}
