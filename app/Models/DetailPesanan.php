<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailPesanan extends Model
{
    protected $table = 'detail_pesanan';
    public $timestamps = false;

    protected $fillable = [
        'pesanan_id',
        'menu_id',
        'paket_id',
        'jumlah',
        'harga_satuan_saat_transaksi'
    ];
}
