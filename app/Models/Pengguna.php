<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Pengguna extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'pengguna';

    public const CREATED_AT = 'dibuat_pada';
    public const UPDATED_AT = null;

    protected $fillable = [
        'nama',
        'email',
        'kata_sandi',
        'no_telepon',
        'peran',
    ];

    protected $hidden = [
        'kata_sandi',
    ];

    protected function casts(): array
    {
        return [
            'kata_sandi' => 'hashed',
            'dibuat_pada' => 'datetime',
        ];
    }
}
