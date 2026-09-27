<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Keranjang extends Model
{
    protected $table = 'keranjang'; 
    public $timestamps = false; 
    protected $fillable = [
        'pengguna_id',
        'diperbarui_pada'
    ];
    

    
    public function isiKeranjang(): HasMany
    {
        return $this->hasMany(IsiKeranjang::class, 'keranjang_id', 'id');
    }
}
