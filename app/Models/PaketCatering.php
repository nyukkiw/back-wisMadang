<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaketCatering extends Model
{
    protected $table = 'paket_catering';

    public $timestamps = false;

    protected $fillable = [
        'nama_paket',
        'harga_paket',
        'deskrips',
    ];
}
