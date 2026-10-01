<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaketCatering extends Model
{
    protected $table = 'paket_catering';

    public $timestamps = false;

    protected $fillable = [
        'nama_paket',
        'harga_paket',
        'deskripsi',
        'porsi',
        'gambar',
        'status_stok',
    ];

    protected $appends = ['gambar_url'];

    public function ulasan(): HasMany
    {
        return $this->hasMany(Ulasan::class, 'paket_id');
    }

    public function getGambarUrlAttribute(): ?string
    {
        return $this->gambar ? asset('storage/' . $this->gambar) : null;
    }
}
