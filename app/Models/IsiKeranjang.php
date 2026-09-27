<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IsiKeranjang extends Model
{
    protected $table = 'isi_keranjang';

    public $timestamps = false;

    protected $fillable = [
        'keranjang_id',
        'menu_id',
        'paket_id',
        'jumlah'
    ];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'menu_id', 'id');
    }

   
    public function paketCatering(): BelongsTo
    {
        return $this->belongsTo(PaketCatering::class, 'paket_id', 'id');
    }
}
