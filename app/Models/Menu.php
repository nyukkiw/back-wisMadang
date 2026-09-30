<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    protected $table = 'menu';

    public $timestamps = false;

    protected $fillable = [
        'kategori_id',
        'nama_menu',
        'harga',
        'deskripsi',
        'status_stok',
        'apakah_laris',
        'gambar',
    ];

    protected $appends = ['gambar_url'];

    protected function casts(): array
    {
        return [
            'harga' => 'decimal:2',
            'apakah_laris' => 'boolean',
        ];
    }

    public function getGambarUrlAttribute(): ?string
    {
        return $this->gambar ? asset('storage/' . $this->gambar) : null;
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }

    public function ulasan(): HasMany
    {
        return $this->hasMany(Ulasan::class, 'menu_id');
    }
}

