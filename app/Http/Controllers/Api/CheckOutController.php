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
use Midtrans\Config;
use Midtrans\Snap;

class CheckoutController extends Controller
{
    // Langkah 1: cuma hitung total & minta Snap Token ke Midtrans. Belum nyimpen apapun ke database,
    // keranjang juga belum disentuh - supaya kalau pembayaran gak jadi dilanjutkan, gak ada yang hilang.
    public function prosesCheckout(Request $request)
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = true;
        Config::$is3ds = true;

        $keranjang = $this->ambilKeranjangBerisi($request->user()->id);

        if ($keranjang instanceof \Illuminate\Http\JsonResponse) {
            return $keranjang;
        }

        $totalBelanja = $this->hitungTotalBelanja($keranjang);
        $idPesananUnik = 'MADANG-2026-' . strtoupper(Str::random(4));

        try {
            $snapToken = Snap::getSnapToken([
                'transaction_details' => [
                    'order_id' => $idPesananUnik,
                    'gross_amount' => (int) round($totalBelanja['total_bayar']),
                ],
                'customer_details' => [
                    'first_name' => $request->user()->nama,
                    'email' => $request->user()->email,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghubungi layanan pembayaran Midtrans.',
                'error' => $e->getMessage()
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Silakan selesaikan pembayaran.',
            'data' => [
                'nomor_pesanan' => $idPesananUnik,
                'subtotal' => $totalBelanja['subtotal'],
                'pajak_10' => $totalBelanja['pajak_10'],
                'total_akhir' => $totalBelanja['total_bayar'],
                'snap_token' => $snapToken,
            ]
        ], 200);
    }

    // Langkah 2: baru dipanggil frontend kalau popup Midtrans bilang pembayaran sukses/pending.
    // Di sinilah pesanan beneran disimpan ke database dan keranjang dikosongkan.
    public function konfirmasiPembayaran(Request $request)
    {
        $data = $request->validate([
            'nomor_pesanan' => ['required', 'string'],
            'metode_pembayaran' => ['required', 'string'],
            'status' => ['required', 'in:diproses,menunggu_pembayaran'],
        ]);

        $userId = $request->user()->id;

        $keranjang = $this->ambilKeranjangBerisi($userId);

        if ($keranjang instanceof \Illuminate\Http\JsonResponse) {
            return $keranjang;
        }

        $totalBelanja = $this->hitungTotalBelanja($keranjang);

        DB::beginTransaction();

        try {
            Pesanan::create([
                'id' => $data['nomor_pesanan'],
                'pelanggan_id' => $userId,
                'promo_id' => null,
                'sesi_shift_id' => null,
                'subtotal' => $totalBelanja['subtotal'],
                'pajak_10' => $totalBelanja['pajak_10'],
                'total_bayar' => $totalBelanja['total_bayar'],
                'status_pesanan' => $data['status'],
                'metode_pembayaran' => $data['metode_pembayaran'],
            ]);

            foreach ($keranjang->isiKeranjang as $item) {
                // Kunci harga produk saat ini agar jika besok admin mengubah harga menu, nota riwayat transaksi lama tidak ikut berubah
                $hargaSaatIni = $item->menu_id ? $item->menu->harga : $item->paketCatering->harga_paket;

                DetailPesanan::create([
                    'pesanan_id' => $data['nomor_pesanan'],
                    'menu_id' => $item->menu_id,
                    'paket_id' => $item->paket_id,
                    'jumlah' => $item->jumlah,
                    'harga_satuan_saat_transaksi' => $hargaSaatIni
                ]);
            }

            IsiKeranjang::where('keranjang_id', $keranjang->id)->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pesanan berhasil dikonfirmasi.',
                'data' => [
                    'nomor_pesanan' => $data['nomor_pesanan'],
                    'status' => $data['status'],
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat konfirmasi pembayaran.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Endpoint ini dipanggil oleh SERVER Midtrans (bukan frontend kita) tiap kali status pembayaran berubah
    public function notifikasiMidtrans(Request $request)
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');

        $notifikasi = new \Midtrans\Notification();

        $pesanan = Pesanan::find($notifikasi->order_id);

        if (!$pesanan) {
            return response()->json(['message' => 'Pesanan tidak ditemukan'], 404);
        }

        $pesanan->status_pesanan = $this->petakanStatusMidtrans($notifikasi->transaction_status, $notifikasi->fraud_status);
        $pesanan->save();

        return response()->json(['message' => 'Notifikasi diterima']);
    }

    // Ambil keranjang milik pengguna, sekalian validasi gak boleh kosong.
    // Kalau kosong, langsung balikin response error (dicek pemanggilnya lewat instanceof JsonResponse).
    private function ambilKeranjangBerisi(int $userId)
    {
        $keranjang = Keranjang::with(['isiKeranjang.menu', 'isiKeranjang.paketCatering'])
            ->where('pengguna_id', $userId)
            ->first();

        if (!$keranjang || $keranjang->isiKeranjang->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Keranjang belanja Anda masih kosong.'
            ], 400);
        }

        return $keranjang;
    }

    // Hitung subtotal, pajak 10%, dan total bayar murni dari harga master di database
    private function hitungTotalBelanja(Keranjang $keranjang): array
    {
        $subtotal = 0;

        foreach ($keranjang->isiKeranjang as $item) {
            if ($item->menu_id) {
                $subtotal += $item->jumlah * $item->menu->harga;
            } elseif ($item->paket_id) {
                $subtotal += $item->jumlah * $item->paketCatering->harga_paket;
            }
        }

        $pajak10 = $subtotal * 0.10;

        return [
            'subtotal' => $subtotal,
            'pajak_10' => $pajak10,
            'total_bayar' => $subtotal + $pajak10,
        ];
    }

    // Menerjemahkan istilah status dari Midtrans jadi istilah status yang dipakai di sistem kita
    private function petakanStatusMidtrans(string $statusTransaksi, ?string $statusFraud): string
    {
        if ($statusTransaksi === 'capture') {
            return $statusFraud === 'accept' ? 'diproses' : 'menunggu_pembayaran';
        }

        if ($statusTransaksi === 'settlement') {
            return 'diproses';
        }

        if (in_array($statusTransaksi, ['cancel', 'deny', 'expire'], true)) {
            return 'dibatalkan';
        }

        return 'menunggu_pembayaran';
    }
}
