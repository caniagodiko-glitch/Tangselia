<?php

use App\Http\Controllers\Api\AdminBeritaController;
use App\Http\Controllers\Api\AdminLowonganController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BeritaController;
use App\Http\Controllers\Api\DirektoriGoogleController;
use App\Http\Controllers\Api\DirektoriImportController;
use App\Http\Controllers\Api\DirektoriTerdekatController;
use App\Http\Controllers\Api\KesehatanController;
use App\Http\Controllers\Api\KulinerController;
use App\Http\Controllers\Api\LowonganController;
use App\Http\Controllers\Api\OlahragaController;
use App\Http\Controllers\Api\UlasanController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::get('/lowongan', [LowonganController::class, 'index']);
Route::get('/lowongan/terbaru', [LowonganController::class, 'terbaru']);
Route::get('/lowongan/meta', [LowonganController::class, 'meta']);
Route::get('/lowongan/{slug}', [LowonganController::class, 'show']);

Route::get('/berita', [BeritaController::class, 'index']);
Route::get('/berita/terbaru', [BeritaController::class, 'terbaru']);
Route::get('/berita/meta', [BeritaController::class, 'meta']);
Route::get('/berita/{slug}', [BeritaController::class, 'show']);

Route::get('/kuliner', [KulinerController::class, 'index']);
Route::get('/kuliner/meta', [KulinerController::class, 'meta']);
Route::get('/kuliner/{slug}', [KulinerController::class, 'show']);

Route::get('/olahraga', [OlahragaController::class, 'index']);
Route::get('/olahraga/meta', [OlahragaController::class, 'meta']);
Route::get('/olahraga/{slug}', [OlahragaController::class, 'show']);

Route::get('/kesehatan', [KesehatanController::class, 'index']);
Route::get('/kesehatan/meta', [KesehatanController::class, 'meta']);
Route::get('/kesehatan/{slug}', [KesehatanController::class, 'show']);

Route::prefix('{fitur}')->group(function () {
    Route::get('{slug}/google', [DirektoriGoogleController::class, 'detail'])
        ->where('fitur', 'kuliner|olahraga|kesehatan');
    Route::get('{slug}/foto', [DirektoriGoogleController::class, 'foto'])
        ->where('fitur', 'kuliner|olahraga|kesehatan');
    Route::get('{slug}/ulasan', [UlasanController::class, 'index'])
        ->where('fitur', 'kuliner|olahraga|kesehatan');
    Route::get('{slug}/terdekat', [DirektoriTerdekatController::class, 'index'])
        ->where('fitur', 'kuliner|olahraga|kesehatan');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    Route::post('/ulasan', [UlasanController::class, 'store']);

    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/ping', function (Request $request) {
            return response()->json(['data' => ['role' => $request->user()->role]]);
        });

        Route::post('/tempat/import', [DirektoriImportController::class, 'tempat']);
        Route::post('/tempat', [KulinerController::class, 'store']);
        Route::put('/tempat/{tempat}', [KulinerController::class, 'update']);
        Route::delete('/tempat/{tempat}', [KulinerController::class, 'destroy']);

        Route::post('/olahraga/import', [DirektoriImportController::class, 'olahraga']);
        Route::post('/olahraga', [OlahragaController::class, 'store']);
        Route::put('/olahraga/{fasilitas}', [OlahragaController::class, 'update']);
        Route::delete('/olahraga/{fasilitas}', [OlahragaController::class, 'destroy']);

        Route::post('/kesehatan/import', [DirektoriImportController::class, 'kesehatan']);
        Route::post('/kesehatan', [KesehatanController::class, 'store']);
        Route::put('/kesehatan/{layanan}', [KesehatanController::class, 'update']);
        Route::delete('/kesehatan/{layanan}', [KesehatanController::class, 'destroy']);

        Route::get('/berita/sumber', [AdminBeritaController::class, 'sumber']);
        Route::post('/berita/refresh', [AdminBeritaController::class, 'refresh']);
        Route::post('/berita', [AdminBeritaController::class, 'store']);
        Route::put('/berita/{berita}', [AdminBeritaController::class, 'update']);
        Route::delete('/berita/{berita}', [AdminBeritaController::class, 'destroy']);

        Route::get('/lowongan', [AdminLowonganController::class, 'index']);
        Route::get('/lowongan/meta', [AdminLowonganController::class, 'meta']);
        Route::put('/lowongan/{lowongan}', [AdminLowonganController::class, 'update']);
        Route::delete('/lowongan/{lowongan}', [AdminLowonganController::class, 'destroy']);
    });
});
