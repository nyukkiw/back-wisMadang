<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use App\Models\Kategori;
use App\Models\Menu;
use App\Models\Pengguna;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CheckOutController;

Route::prefix('v1')->group(function () {
    Route::get('/keranjang', [CartController::class, 'ambilIsiKeranjang']);
    Route::post('/keranjang/simpan', [CartController::class, 'simpanKeKeranjang']);
    Route::post('/pesanan/checkout', [CheckoutController::class, 'prosesCheckout']); 
    
});
Route::post('/register', function (Request $request) {
    $data = $request->validate([
        'nama' => ['required', 'string', 'max:100'],
        'email' => ['required', 'email', 'max:100', 'unique:pengguna,email'],
        'kata_sandi' => ['required', 'string', 'min:8', 'max:255'],
        'no_telepon' => ['nullable', 'string', 'max:20'],
        'peran' => ['required', Rule::in(['admin', 'kasir', 'pelanggan'])],
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
        'email' => ['required', 'email'],
        'kata_sandi' => ['required', 'string'],
    ]);

    $pengguna = Pengguna::where('email', $data['email'])->first();

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

Route::middleware(['auth:sanctum', 'role:kasir,admin'])->group(function () {
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
        'kata_sandi' => ['required', 'string', 'min:8', 'max:255'],
        'no_telepon' => ['nullable', 'string', 'max:20'],
        'peran' => ['required', Rule::in(['admin', 'kasir', 'pelanggan'])],
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
        'kata_sandi' => ['sometimes', 'string', 'min:8', 'max:255'],
        'no_telepon' => ['nullable', 'string', 'max:20'],
        'peran' => ['sometimes', Rule::in(['admin', 'kasir', 'pelanggan'])],
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
    return response()->json(Menu::with('kategori')->get());
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

Route::post('/menu', function (Request $request) {
    $data = $request->validate([
        'kategori_id' => ['required', 'integer', 'exists:kategori,id'],
        'nama_menu' => ['required', 'string', 'max:100'],
        'harga' => ['required', 'numeric', 'min:0'],
        'deskripsi' => ['nullable', 'string'],
        'status_stok' => ['sometimes', Rule::in(['tersedia', 'habis'])],
        'apakah_laris' => ['sometimes', 'boolean'],
    ]);

    $data['status_stok'] ??= 'tersedia';
    $data['apakah_laris'] ??= false;

    return response()->json(Menu::create($data)->load('kategori'), 201);
});
