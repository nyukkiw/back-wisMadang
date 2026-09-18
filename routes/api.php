<?php


// nah route harus disini, 
// seperti mengambil data dari database, enkripsi password, proses validasi data, dll.


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use App\Models\Pengguna;




Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::get('/test-koneksi', function () {
    return response()->json([
        'pesan' => 'Halo Tim! Koneksi dari Laravel ke Next.js berhasil 🚀',
        'status' => 'Aman Jaya'
    ]);
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
