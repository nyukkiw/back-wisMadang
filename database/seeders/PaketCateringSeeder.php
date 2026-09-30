<?php

namespace Database\Seeders;

use App\Models\PaketCatering;
use Illuminate\Database\Seeder;

class PaketCateringSeeder extends Seeder
{
    public function run(): void
    {
        $paketCatering = [
            [
                'nama_paket' => 'Paket Hemat Keluarga',
                'harga_paket' => 85000,
                'deskripsi' => 'Nasi, ayam, sayur, sambal, dan minuman.',
                'porsi' => 4,
            ],
            [
                'nama_paket' => 'Paket Rapat',
                'harga_paket' => 150000,
                'deskripsi' => 'Paket makan siang untuk acara kantor.',
                'porsi' => 8,
            ],
            [
                'nama_paket' => 'Paket Acara Besar',
                'harga_paket' => 350000,
                'deskripsi' => 'Paket catering prasmanan untuk acara keluarga.',
                'porsi' => 20,
            ],
        ];

        foreach ($paketCatering as $data) {
            PaketCatering::updateOrCreate(
                ['nama_paket' => $data['nama_paket']],
                $data
            );
        }
    }
}
