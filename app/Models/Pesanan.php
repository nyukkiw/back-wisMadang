<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pesanan extends Model
{
    protected $table = 'pesanan';
    public $timestamps = false;

    // ⚠️ PENTING: Karena ID kita formatnya string unik (bukan angka berurutan), matikan auto increment
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'pelanggan_id',
        'promo_id',
        'sesi_shift_id',
        'subtotal',
        'pajak_10',
        'total_bayar',
        'status_pesanan',
        'metode_pembayaran',
    ];

    public function detailPesanan(): HasMany
    {
        return $this->hasMany(DetailPesanan::class, 'pesanan_id', 'id');
    }

    public function ulasan(): HasMany
    {
        return $this->hasMany(Ulasan::class, 'pesanan_id', 'id');
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'pelanggan_id');
    }
}
