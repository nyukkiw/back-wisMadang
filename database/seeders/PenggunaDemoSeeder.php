<?php

namespace Database\Seeders;

use App\Models\Pengguna;
use Illuminate\Database\Seeder;

class PenggunaDemoSeeder extends Seeder
{
    // Akun contoh buat login sebagai penjual (admin), karena form Register di frontend
    // cuma bisa dipakai untuk daftar sebagai pelanggan.
    public function run(): void
    {
        Pengguna::updateOrCreate(
            ['email' => 'penjual@wismadang.com'],
            [
                'nama' => 'Penjual Wis Madang',
                'kata_sandi' => 'penjual123',
                'peran' => 'penjual',
            ]
        );
    }
}
