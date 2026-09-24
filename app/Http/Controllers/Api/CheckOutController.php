<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Keranjang;
use App\Models\IsiKeranjang;
use App\Models\Pesanan;
use App\Models\DetailPesanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function prosesCheckout(Request $request)
    {
        // Asumsi ID Pengguna manual = 1 untuk keperluan testing sebelum token digabung
        $userId = 1;

        // 1. Ambil wadah keranjang aktif pengguna beserta seluruh isinya langsung dari database
        $keranjang = Keranjang::with(['isiKeranjang.menu', 'isiKeranjang.paketCatering'])
            ->where('pengguna_id', $userId)
            ->first();

        // Validasi: Jika keranjang tidak ditemukan atau isinya kosong melompong, batalkan transaksi
        if (!$keranjang || $keranjang->isiKeranjang->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal checkout, keranjang belanja Anda masih kosong.'
            ], 400);
        }

        // 2. LOGIKA FINANSIAL UTAMA: Hitung subtotal asli murni dari database master
        $subtotalAsli = 0;
        
        foreach ($keranjang->isiKeranjang as $item) {
            if ($item->menu_id) {
                // Kalikan jumlah porsi dengan harga master dari tabel 'menu'
                $subtotalAsli += $item->jumlah * $item->menu->harga;
            } elseif ($item->paket_id) {
                // Kalikan jumlah porsi dengan harga master dari tabel 'paket_catering'
                $subtotalAsli += $item->jumlah * $item->paketCatering->harga;
            }
        }

        // 3. Hitung Pajak 10% & Total Bayar Akhir
        $pajak10 = $subtotalAsli * 0.10;
        $totalBayar = $subtotalAsli + $pajak10;

        // 4. Generate String ID Unik format: MADANG-2026-XXXX (4 digit random alphanumeric besar)
        $idPesananUnik = 'MADANG-2026-' . strtoupper(Str::random(4));

        // 🔒 GUNAKAN DATABASE TRANSACTION: Memastikan jika di tengah jalan ada eror kodingan, 
        // database otomatis membatalkan seluruh inputan agar data tidak korup/setengah masuk.
        DB::beginTransaction();

        try {
            // Langkah A: Buat baris baru di tabel pesanan utama
            $pesanan = Pesanan::create([
                'id' => $idPesananUnik,
                'pelanggan_id' => $userId,
                'promo_id' => null, // Bisa diintegrasikan nanti
                'sesi_shift_id' => null, // Bisa diintegrasikan nanti
                'subtotal' => $subtotalAsli,
                'pajak_10' => $pajak10,
                'total_bayar' => $totalBayar,
                'status_pesanan' => 'diproses', // Status awal antrean kasir
                'metode_pembayaran' => $request->metode_pembayaran ?? 'tunai'
            ]);

            // Langkah B: Pindahkan item dari isi_keranjang ke tabel detail_pesanan
            foreach ($keranjang->isiKeranjang as $item) {
                // Kunci harga produk saat ini agar jika besok admin mengubah harga menu, nota riwayat transaksi lama tidak ikut berubah
                $hargaSaatIni = $item->menu_id ? $item->menu->harga : $item->paketCatering->harga;

                DetailPesanan::create([
                    'pesanan_id' => $idPesananUnik,
                    'menu_id' => $item->menu_id,
                    'paket_id' => $item->paket_id,
                    'jumlah' => $item->jumlah,
                    'harga_satuan_saat_transaksi' => $hargaSaatIni
                ]);
            }

            // Langkah C: Kosongkan/hapus seluruh isi keranjang belanja user tersebut secara permanen
            IsiKeranjang::where('keranjang_id', $keranjang->id)->delete();

            // Selesaikan transaksi database, simpan permanen
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Checkout berhasil! Pesanan Anda telah terdaftar di sistem.',
                'data' => [
                    'nomor_pesanan' => $idPesananUnik,
                    'subtotal' => $subtotalAsli,
                    'pajak_10' => $pajak10,
                    'total_akhir' => $totalBayar,
                    'status' => 'diproses'
                ]
            ], 201);

        } catch (\Exception $e) {
            // Jika ada yang gagal/eror di tengah jalan, batalkan semua inputan tabel di atas
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat checkout.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
