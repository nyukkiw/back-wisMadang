<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pesanan;
use App\Models\Ulasan;
use App\Services\AnalisisSentimen;
use App\Services\InsightUlasan;
use Illuminate\Http\Request;

class UlasanController extends Controller
{
    public function __construct(
        private AnalisisSentimen $analisisSentimen,
        private InsightUlasan $insightUlasan,
    ) {
    }

    // Daftar pesanan milik user yang login, lengkap sama isinya & ulasan yang sudah pernah dikirim
    public function riwayatPesanan(Request $request)
    {
        $pesanan = Pesanan::with(['detailPesanan.menu', 'detailPesanan.paketCatering', 'ulasan'])
            ->where('pelanggan_id', $request->user()->id)
            ->orderByDesc('dibuat_pada')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $pesanan,
        ]);
    }

    // Kirim ulasan untuk 1 atau lebih item dalam 1 pesanan sekaligus
    public function kirimUlasan(Request $request)
    {
        $data = $request->validate([
            'pesanan_id' => ['required', 'string', 'exists:pesanan,id'],
            'ulasan' => ['required', 'array', 'min:1'],
            'ulasan.*.menu_id' => ['nullable', 'integer', 'exists:menu,id'],
            'ulasan.*.paket_id' => ['nullable', 'integer', 'exists:paket_catering,id'],
            'ulasan.*.rating' => ['required', 'integer', 'min:1', 'max:5'],
            'ulasan.*.komentar' => ['nullable', 'string', 'max:500'],
        ]);

        // Pastikan pesanan ini beneran punya user yang sedang login, bukan punya orang lain
        $pesanan = Pesanan::where('id', $data['pesanan_id'])
            ->where('pelanggan_id', $request->user()->id)
            ->first();

        if (!$pesanan) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan tidak ditemukan.',
            ], 404);
        }

        $ulasanTersimpan = [];

        foreach ($data['ulasan'] as $item) {
            if (empty($item['menu_id']) && empty($item['paket_id'])) {
                continue;
            }

            // updateOrCreate supaya kalau user kirim ulasan lagi buat item yang sama, isinya diperbarui (bukan numpuk baris baru)
            $ulasanTersimpan[] = Ulasan::updateOrCreate(
                [
                    'pesanan_id' => $data['pesanan_id'],
                    'menu_id' => $item['menu_id'] ?? null,
                    'paket_id' => $item['paket_id'] ?? null,
                ],
                [
                    'rating' => $item['rating'],
                    'komentar' => $item['komentar'] ?? null,
                    'sentimen_ai' => $this->analisisSentimen->analisis($item['komentar'] ?? null),
                    'dibuat_pada' => now(),
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Ulasan berhasil dikirim.',
            'data' => $ulasanTersimpan,
        ], 201);
    }

    // Khusus admin (penjual): daftar SEMUA ulasan dari SEMUA pelanggan, buat dashboard AI Insight
    public function semuaUlasan()
    {
        $ulasan = Ulasan::with(['menu', 'paketCatering', 'pesanan.pengguna'])
            ->orderByDesc('dibuat_pada')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $ulasan,
        ]);
    }

    // Khusus admin (penjual): minta AI bikin ringkasan, sinyal utama, & saran aksi dari SEMUA ulasan sekaligus
    public function insightAi()
    {
        $daftarUlasan = Ulasan::whereNotNull('komentar')
            ->get(['rating', 'komentar'])
            ->toArray();

        $insight = $this->insightUlasan->buatInsight($daftarUlasan);

        if ($insight === null) {
            return response()->json([
                'success' => false,
                'message' => 'Belum cukup data ulasan buat dianalisis, atau layanan AI sedang gangguan.',
            ], 200);
        }

        return response()->json([
            'success' => true,
            'data' => $insight,
        ]);
    }

}
