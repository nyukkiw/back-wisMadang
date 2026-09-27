<?php

namespace Database\Seeders;

use App\Models\Kategori;
use App\Models\Menu;
use Illuminate\Database\Seeder;

class KategoriMenuSeeder extends Seeder
{
    public function run(): void
    {
        $kategori = collect([
            'Nasi',
            'Lauk',
            'Sayur',
            'Minuman',
        ])->mapWithKeys(function (string $namaKategori) {
            $data = Kategori::updateOrCreate(
                ['nama_kategori' => $namaKategori],
                ['nama_kategori' => $namaKategori]
            );

            return [$namaKategori => $data->id];
        });

        $menu = [
            [
                'kategori_id' => $kategori['Nasi'],
                'nama_menu' => 'Nasi Putih',
                'harga' => 5000,
                'deskripsi' => 'Nasi putih hangat.',
                'status_stok' => 'tersedia',
                'apakah_laris' => true,
            ],
            [
                'kategori_id' => $kategori['Nasi'],
                'nama_menu' => 'Nasi Goreng Spesial',
                'harga' => 25000,
                'deskripsi' => 'Nasi goreng dengan telur dan ayam.',
                'status_stok' => 'tersedia',
                'apakah_laris' => true,
            ],
            [
                'kategori_id' => $kategori['Lauk'],
                'nama_menu' => 'Ayam Goreng',
                'harga' => 18000,
                'deskripsi' => 'Ayam goreng gurih dan renyah.',
                'status_stok' => 'tersedia',
                'apakah_laris' => true,
            ],
            [
                'kategori_id' => $kategori['Lauk'],
                'nama_menu' => 'Telur Balado',
                'harga' => 10000,
                'deskripsi' => 'Telur dengan sambal balado.',
                'status_stok' => 'tersedia',
                'apakah_laris' => false,
            ],
            [
                'kategori_id' => $kategori['Sayur'],
                'nama_menu' => 'Sayur Asem',
                'harga' => 10000,
                'deskripsi' => 'Sayur asem segar dengan kuah khas.',
                'status_stok' => 'tersedia',
                'apakah_laris' => false,
            ],
            [
                'kategori_id' => $kategori['Minuman'],
                'nama_menu' => 'Es Teh Manis',
                'harga' => 5000,
                'deskripsi' => 'Teh manis dingin.',
                'status_stok' => 'tersedia',
                'apakah_laris' => true,
            ],
            [
                'kategori_id' => $kategori['Minuman'],
                'nama_menu' => 'Jus Alpukat',
                'harga' => 15000,
                'deskripsi' => 'Jus alpukat segar.',
                'status_stok' => 'tersedia',
                'apakah_laris' => false,
            ],
        ];

        foreach ($menu as $data) {
            Menu::updateOrCreate(
                [
                    'kategori_id' => $data['kategori_id'],
                    'nama_menu' => $data['nama_menu'],
                ],
                $data
            );
        }
    }
}