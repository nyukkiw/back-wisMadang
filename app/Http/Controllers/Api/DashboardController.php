<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pesanan;

class DashboardController extends Controller
{
    // Ringkasan bisnis buat dashboard admin - omzet & jumlah pesanan hari ini yang sudah dibayar
    public function ringkasan()
    {
        $pesananHariIni = Pesanan::where('status_pesanan', 'diproses')
            ->whereDate('dibuat_pada', today());

        return response()->json([
            'success' => true,
            'data' => [
                'omzet_hari_ini' => (float) $pesananHariIni->sum('total_bayar'),
                'total_pesanan_hari_ini' => $pesananHariIni->count(),
            ],
        ]);
    }
}
