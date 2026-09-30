<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ulasan extends Model
{
    protected $table = 'ulasan';

    public $timestamps = false;

    protected $fillable = [
        'pesanan_id',
        'menu_id',
        'paket_id',
        'rating',
        'komentar',
        'sentimen_ai',
    ];

    protected function casts(): array
    {
        return [
            'dibuat_pada' => 'datetime',
        ];
    }

    public function pesanan(): BelongsTo
    {
        return $this->belongsTo(Pesanan::class, 'pesanan_id');
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'menu_id');
    }

    public function paketCatering(): BelongsTo
    {
        return $this->belongsTo(PaketCatering::class, 'paket_id');
    }
}
