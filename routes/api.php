<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Models\Kategori;
use App\Models\Menu;
use App\Models\Pengguna;
use App\Models\PaketCatering;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CheckOutController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\UlasanController;

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::get('/keranjang', [CartController::class, 'ambilIsiKeranjang']);
    Route::post('/keranjang/simpan', [CartController::class, 'simpanKeKeranjang']);
    Route::post('/pesanan/checkout', [CheckoutController::class, 'prosesCheckout']);
    Route::post('/pesanan/konfirmasi', [CheckoutController::class, 'konfirmasiPembayaran']);
    Route::get('/pesanan', [UlasanController::class, 'riwayatPesanan']);
    Route::post('/ulasan', [UlasanController::class, 'kirimUlasan']);

});

// Khusus penjual (admin) - lihat semua ulasan dari semua pelanggan
Route::middleware(['auth:sanctum', 'role:penjual'])->group(function () {
    Route::get('/v1/ulasan', [UlasanController::class, 'semuaUlasan']);
    Route::get('/v1/ulasan/insight', [UlasanController::class, 'insightAi']);
    Route::get('/v1/dashboard/ringkasan', [DashboardController::class, 'ringkasan']);
});

// Dipanggil langsung oleh server Midtrans, bukan oleh pengguna yang login - jadi di luar middleware auth:sanctum
Route::post('/midtrans/notification', [CheckOutController::class, 'notifikasiMidtrans']);
Route::post('/register', function (Request $request) {
    $data = $request->validate([
        'nama' => ['required', 'string', 'max:100'],
        'email' => ['required', 'email', 'max:100', 'unique:pengguna,email'],
        'kata_sandi' => ['required', 'string', 'min:6', 'max:255'],
        'no_telepon' => ['nullable', 'string', 'max:20'],
        'peran' => ['required', Rule::in(['pelanggan', 'penjual'])],
    ]);

    $pengguna = Pengguna::create($data);
    $token = $pengguna->createToken('postman')->plainTextToken;

    return response()->json([
        'pesan' => 'Registrasi berhasil',
        'pengguna' => $pengguna,
        'token' => $token,
    ], 201);
});

Route::post('/login', function (Request $request) {
    $data = $request->validate([
        'email' => ['required_without:nama', 'nullable', 'email'],
        'nama' => ['required_without:email', 'nullable', 'string'],
        'kata_sandi' => ['required', 'string'],
    ]);

    $query = Pengguna::query();

    if (! empty($data['email'])) {
        $query->where('email', $data['email']);
    } else {
        $query->where('nama', $data['nama']);
    }

    $pengguna = $query->first();

    if (! $pengguna || ! Hash::check($data['kata_sandi'], $pengguna->kata_sandi)) {
        return response()->json([
            'pesan' => 'Email atau kata sandi salah',
        ], 401);
    }

    $token = $pengguna->createToken('postman')->plainTextToken;

    return response()->json([
        'pesan' => 'Login berhasil',
        'pengguna' => $pengguna,
        'token' => $token,
    ]);
});

Route::post('/v1/auth/login', function (Request $request) {
    $data = $request->validate([
        'email' => ['required', 'email'],
        'kata_sandi' => ['required', 'string'],
    ]);

    $pengguna = Pengguna::where('email', $data['email'])->first();

    if (! $pengguna || ! Hash::check($data['kata_sandi'], $pengguna->kata_sandi)) {
        return response()->json([
            'pesan' => 'Email atau kata sandi salah',
        ], 401);
    }

    $token = $pengguna->createToken('api-v1')->plainTextToken;

    return response()->json([
        'pesan' => 'Login berhasil',
        'token' => $token,
        'peran' => $pengguna->peran,
    ]);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', function (Request $request) {
        return response()->json($request->user());
    });

    Route::post('/logout', function (Request $request) {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'pesan' => 'Logout berhasil',
        ]);
    });
});

Route::middleware(['auth:sanctum', 'role:penjual'])->group(function () {
    Route::get('/shift', function (Request $request) {
        return response()->json([
            'pesan' => 'Data shift kasir dapat diakses.',
            'diakses_oleh' => $request->user()->peran,
        ]);
    });
});

Route::get('/pengguna', function () {
    return response()->json(Pengguna::all());
});

Route::post('/pengguna', function (Request $request) {
    $data = $request->validate([
        'nama' => ['required', 'string', 'max:100'],
        'email' => ['required', 'email', 'max:100', 'unique:pengguna,email'],
        'kata_sandi' => ['required', 'string', 'min:6', 'max:255'],
        'no_telepon' => ['nullable', 'string', 'max:20'],
        'peran' => ['required', Rule::in(['pelanggan', 'penjual'])],
    ]);

    $pengguna = Pengguna::create($data);

    return response()->json($pengguna, 201);
});

Route::get('/pengguna/{id}', function (int $id) {
    return response()->json(Pengguna::findOrFail($id));
});

Route::match(['put', 'patch'], '/pengguna/{id}', function (Request $request, int $id) {
    $pengguna = Pengguna::findOrFail($id);

    $data = $request->validate([
        'nama' => ['sometimes', 'string', 'max:100'],
        'email' => [
            'sometimes',
            'email',
            'max:100',
            Rule::unique('pengguna', 'email')->ignore($pengguna->id),
        ],
        'kata_sandi' => ['sometimes', 'string', 'min:6', 'max:255'],
        'no_telepon' => ['nullable', 'string', 'max:20'],
        'peran' => ['sometimes', Rule::in(['pelanggan', 'penjual'])],
    ]);

    $pengguna->update($data);

    return response()->json($pengguna->fresh());
});

Route::delete('/pengguna/{id}', function (int $id) {
    $pengguna = Pengguna::findOrFail($id);
    $pengguna->delete();

    return response()->json([
        'pesan' => 'Pengguna berhasil dihapus',
    ]);
});

Route::get('/kategori', function () {
    return response()->json(Kategori::with('menu')->get());
});

Route::post('/kategori', function (Request $request) {
    $data = $request->validate([
        'nama_kategori' => ['required', 'string', 'max:50'],
    ]);

    return response()->json(Kategori::create($data), 201);
});

Route::get('/menu', function () {
    return response()->json(
        Menu::with('kategori')
            ->withAvg('ulasan', 'rating')
            ->withCount('ulasan')
            ->get()
    );
});

Route::get('/paket-catering', function () {
    return response()->json(
        PaketCatering::withAvg('ulasan', 'rating')
            ->withCount('ulasan')
            ->get()
    );
});

Route::get('/v1/menu', function (Request $request) {
    $data = $request->validate([
        'kategori_id' => ['sometimes', 'integer', 'exists:kategori,id'],
        'cari' => ['sometimes', 'string', 'max:100'],
    ]);

    $query = Menu::with('kategori');

    if (isset($data['kategori_id'])) {
        $query->where('kategori_id', $data['kategori_id']);
    }

    if (! empty($data['cari'])) {
        $kataKunci = strtolower(str_replace(' ', '', $data['cari']));

        $query->whereRaw(
            "LOWER(REPLACE(nama_menu, ' ', '')) LIKE ?",
            ["%{$kataKunci}%"]
        );
    }

    return response()->json($query->get());
});

// Kelola menu & paket catering (bikin/ubah/hapus) cuma boleh dilakukan penjual yang login
Route::middleware(['auth:sanctum', 'role:penjual'])->group(function () {
    Route::post('/menu', function (Request $request) {
        $data = $request->validate([
            'kategori_id' => ['required', 'integer', 'exists:kategori,id'],
            'nama_menu' => ['required', 'string', 'max:100'],
            'harga' => ['required', 'numeric', 'min:0'],
            'deskripsi' => ['nullable', 'string'],
            'status_stok' => ['sometimes', Rule::in(['tersedia', 'habis'])],
        ]);

        $data['status_stok'] ??= 'tersedia';

        return response()->json(Menu::create($data)->load('kategori'), 201);
    });

    Route::match(['put', 'patch'], '/menu/{id}', function (Request $request, int $id) {
        $menu = Menu::findOrFail($id);

        $data = $request->validate([
            'kategori_id' => ['sometimes', 'integer', 'exists:kategori,id'],
            'nama_menu' => ['sometimes', 'string', 'max:100'],
            'harga' => ['sometimes', 'numeric', 'min:0'],
            'deskripsi' => ['nullable', 'string'],
            'status_stok' => ['sometimes', Rule::in(['tersedia', 'habis'])],
        ]);

        $menu->update($data);

        return response()->json($menu->fresh()->load('kategori'));
    });

    Route::delete('/menu/{id}', function (int $id) {
        $menu = Menu::findOrFail($id);

        try {
            $menu->delete();
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'pesan' => 'Menu ini sudah pernah dipesan pelanggan, jadi tidak bisa dihapus permanen (supaya riwayat pesanan lama tidak rusak). Pakai tombol Nonaktifkan saja.',
            ], 409);
        }

        return response()->json([
            'pesan' => 'Menu berhasil dihapus',
        ]);
    });

    Route::post('/menu/{id}/gambar', function (Request $request, int $id) {
        $menu = Menu::findOrFail($id);

        $request->validate([
            'gambar' => ['required', 'image', 'max:2048'],
        ]);

        // Hapus file gambar lama dulu supaya gak numpuk file yang gak kepakai
        if ($menu->gambar) {
            Storage::disk('public')->delete($menu->gambar);
        }

        $path = $request->file('gambar')->store('menu', 'public');
        $menu->update(['gambar' => $path]);

        return response()->json($menu->fresh()->load('kategori'));
    });

    Route::post('/paket-catering', function (Request $request) {
        $data = $request->validate([
            'nama_paket' => ['required', 'string', 'max:100'],
            'harga_paket' => ['required', 'numeric', 'min:0'],
            'deskripsi' => ['nullable', 'string'],
            'porsi' => ['required', 'integer', 'min:1'],
        ]);

        return response()->json(PaketCatering::create($data), 201);
    });

    Route::match(['put', 'patch'], '/paket-catering/{id}', function (Request $request, int $id) {
        $paketCatering = PaketCatering::findOrFail($id);

        $data = $request->validate([
            'nama_paket' => ['sometimes', 'string', 'max:100'],
            'harga_paket' => ['sometimes', 'numeric', 'min:0'],
            'deskripsi' => ['nullable', 'string'],
            'porsi' => ['sometimes', 'integer', 'min:1'],
            'status_stok' => ['sometimes', Rule::in(['tersedia', 'habis'])],
        ]);

        $paketCatering->update($data);

        return response()->json($paketCatering->fresh());
    });

    Route::delete('/paket-catering/{id}', function (int $id) {
        $paketCatering = PaketCatering::findOrFail($id);

        try {
            $paketCatering->delete();
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'pesan' => 'Paket ini sudah pernah dipesan pelanggan, jadi tidak bisa dihapus permanen (supaya riwayat pesanan lama tidak rusak).',
            ], 409);
        }

        return response()->json([
            'pesan' => 'Paket catering berhasil dihapus',
        ]);
    });

    Route::post('/paket-catering/{id}/gambar', function (Request $request, int $id) {
        $paketCatering = PaketCatering::findOrFail($id);

        $request->validate([
            'gambar' => ['required', 'image', 'max:2048'],
        ]);

        if ($paketCatering->gambar) {
            Storage::disk('public')->delete($paketCatering->gambar);
        }

        $path = $request->file('gambar')->store('paket-catering', 'public');
        $paketCatering->update(['gambar' => $path]);

        return response()->json($paketCatering->fresh());
    });
});
