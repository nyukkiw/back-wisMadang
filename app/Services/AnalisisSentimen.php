<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AnalisisSentimen
{
    private const PILIHAN_LABEL = ['positif', 'netral', 'negatif'];

    // Baca komentar ulasan, minta AI nentuin sentimennya.
    // Balikin null kalau komentar kosong atau AI-nya gagal dihubungi (jangan sampai ini bikin ulasan gagal tersimpan).
    public function analisis(?string $komentar): ?string
    {
        $komentar = trim((string) $komentar);

        if ($komentar === '') {
            return null;
        }

        try {
            $respons = Http::withToken(config('services.edgeone_ai.api_key'))
                ->post('https://ai-gateway.edgeone.link/v1/chat/completions', [
                    'model' => '@makers/deepseek-v4-flash',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Kamu adalah alat klasifikasi sentimen ulasan pelanggan restoran. Baca komentar pelanggan, lalu balas HANYA dengan satu kata: positif, netral, atau negatif. Jangan tambah penjelasan apapun.',
                        ],
                        ['role' => 'user', 'content' => $komentar],
                    ],
                ]);

            if (!$respons->successful()) {
                report(new \Exception('Gagal memanggil layanan analisis sentimen: ' . $respons->body()));
                return null;
            }

            $label = strtolower(trim((string) $respons->json('choices.0.message.content')));

            return in_array($label, self::PILIHAN_LABEL, true) ? $label : null;
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }
}
