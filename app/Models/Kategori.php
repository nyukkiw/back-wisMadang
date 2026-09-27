<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kategori extends Model
{
    protected $table = 'kategori';

    public $timestamps = false;

    protected $fillable = [
        'nama_kategori',
    ];

    public function menu(): HasMany
    {
        return $this->hasMany(Menu::class, 'kategori_id');
    }
}