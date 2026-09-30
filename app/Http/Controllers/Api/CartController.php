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
        $userId = $request->user()->id;
        // Cari keranjang aktif milik user, beserta rincian menu satuan atau paket kateringnya
        $keranjang = Keranjang::with(['isiKeranjang.menu.kategori', 'isiKeranjang.paketCatering'])
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
        $validator = Validator::make($request->json()->all(), [
            'menu_id'  => 'nullable|integer|exists:menu,id',
            'paket_id' => 'nullable|integer|exists:paket_catering,id',
            'jumlah'   => 'required|integer|min:1',
            'aksi'     => 'required|in:tambah,kurangi,hapus',
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

        // Pastikan wadah "keranjang" user sudah ada di database. Jika belum, buat baru otomatis.
        $keranjang = Keranjang::firstOrCreate(
            ['pengguna_id' => $request->user()->id],
            ['diperbarui_pada' => now()]
        );

        // Cari apakah makanan/paket ini sudah pernah dimasukkan ke dalam keranjang tersebut
        $itemKeranjang = IsiKeranjang::where([
            'keranjang_id' => $keranjang->id,
            'menu_id'      => $request->menu_id,
            'paket_id'     => $request->paket_id,
        ])->first();

        if ($request->aksi === 'hapus') {
            return $this->hapusItemKeranjang($itemKeranjang);
        }

        if ($request->aksi === 'kurangi') {
            return $this->kurangiItemKeranjang($itemKeranjang, $request->jumlah);
        }

        return $this->tambahItemKeranjang($itemKeranjang, $keranjang, $request);
    }

    // Menghapus item dari keranjang, berapa pun jumlahnya
    private function hapusItemKeranjang(?IsiKeranjang $itemKeranjang)
    {
        if (!$itemKeranjang) {
            return response()->json([
                'success' => false,
                'message' => 'Item memang tidak ada di dalam keranjang'
            ], 404);
        }

        $itemKeranjang->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item berhasil dihapus dari keranjang belanja'
        ], 200);
    }

    // Mengurangi jumlah item. Kalau hasilnya 0 atau kurang, item langsung dihapus
    private function kurangiItemKeranjang(?IsiKeranjang $itemKeranjang, int $jumlahKurang)
    {
        if (!$itemKeranjang) {
            return response()->json([
                'success' => false,
                'message' => 'Item memang tidak ada di dalam keranjang'
            ], 404);
        }

        $jumlahBaru = $itemKeranjang->jumlah - $jumlahKurang;

        if ($jumlahBaru <= 0) {
            return $this->hapusItemKeranjang($itemKeranjang);
        }

        $itemKeranjang->jumlah = $jumlahBaru;
        $itemKeranjang->save();

        return response()->json([
            'success' => true,
            'message' => 'Jumlah item berhasil dikurangi',
            'data'    => $itemKeranjang
        ], 200);
    }

    // Menambah jumlah item yang sudah ada, atau membuat baris baru kalau belum pernah ditambahkan
    private function tambahItemKeranjang(?IsiKeranjang $itemKeranjang, Keranjang $keranjang, Request $request)
    {
        if ($itemKeranjang) {
            $itemKeranjang->jumlah += $request->jumlah;
            $itemKeranjang->save();

            return response()->json([
                'success' => true,
                'message' => 'Jumlah item berhasil ditambahkan di keranjang',
                'data'    => $itemKeranjang
            ], 200);
        }

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
