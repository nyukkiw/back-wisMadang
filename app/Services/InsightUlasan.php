<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class InsightUlasan
{
    // Kirim SEMUA ulasan sekaligus ke AI, minta dia bikin ringkasan, sinyal utama, dan saran aksi.
    // Balikin null kalau gak ada ulasan buat dianalisis, atau AI-nya gagal dihubungi/gagal dibaca hasilnya.
    public function buatInsight(array $daftarUlasan): ?array
    {
        $daftarUlasan = array_values(array_filter($daftarUlasan, fn (array $u) => trim((string) ($u['komentar'] ?? '')) !== ''));

        if (count($daftarUlasan) === 0) {
            return null;
        }

        $teksUlasan = collect($daftarUlasan)
            ->map(fn (array $u) => "Rating {$u['rating']}/5: {$u['komentar']}")
            ->implode("\n");

        try {
            $respons = Http::withToken(config('services.edgeone_ai.api_key'))
                ->post('https://ai-gateway.edgeone.link/v1/chat/completions', [
                    'model' => '@makers/deepseek-v4-flash',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Kamu adalah asisten analisis bisnis restoran. Kamu akan diberi kumpulan ulasan pelanggan (rating & komentar). Buat: '
                                . '(1) topik_utama: topik yang paling sering dibahas, ditulis SANGAT SINGKAT (2-4 kata, contoh: "Kecepatan pelayanan" atau "Rasa nasi goreng"), '
                                . '(2) ringkasan: kondisi kepuasan pelanggan secara umum dalam 1 kalimat, '
                                . '(3) sinyal_utama: penjelasan lebih detail soal topik_utama tadi (1 kalimat, boleh lebih panjang), '
                                . '(4) saran_aksi: saran aksi konkret buat pemilik restoran. '
                                . 'Balas HANYA dalam format JSON persis seperti ini, tanpa teks lain di luar JSON: '
                                . '{"topik_utama": "...", "ringkasan": "...", "sinyal_utama": "...", "saran_aksi": "..."}',
                        ],
                        ['role' => 'user', 'content' => $teksUlasan],
                    ],
                ]);

            if (!$respons->successful()) {
                report(new \Exception('Gagal memanggil layanan insight AI: ' . $respons->body()));
                return null;
            }

            
            $teksJawaban = (string) $respons->json('choices.0.message.content');
            $hasil = json_decode($teksJawaban, true);

            if (!is_array($hasil) || !isset($hasil['topik_utama'], $hasil['ringkasan'], $hasil['sinyal_utama'], $hasil['saran_aksi'])) {
                report(new \Exception('Jawaban AI bukan format JSON yang diharapkan: ' . $teksJawaban));
                return null;
            }

            return [
                'topik_utama' => $hasil['topik_utama'],
                'ringkasan' => $hasil['ringkasan'],
                'sinyal_utama' => $hasil['sinyal_utama'],
                'saran_aksi' => $hasil['saran_aksi'],
            ];
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }
}
