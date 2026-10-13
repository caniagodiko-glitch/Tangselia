<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OsmImporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DirektoriImportController extends Controller
{
    /**
     * Impor direktori kuliner dari OpenStreetMap (admin).
     */
    public function tempat(Request $request, OsmImporter $importer): JsonResponse
    {
        return $this->jalankan($request, $importer, 'tempat');
    }

    /**
     * Impor direktori olahraga dari OpenStreetMap (admin).
     */
    public function olahraga(Request $request, OsmImporter $importer): JsonResponse
    {
        return $this->jalankan($request, $importer, 'olahraga');
    }

    /**
     * Impor direktori kesehatan dari OpenStreetMap (admin).
     */
    public function kesehatan(Request $request, OsmImporter $importer): JsonResponse
    {
        return $this->jalankan($request, $importer, 'kesehatan');
    }

    private function jalankan(Request $request, OsmImporter $importer, string $fitur): JsonResponse
    {
        $validated = $request->validate([
            'jenis' => ['nullable', 'string', 'max:50'],
        ]);

        $stats = $importer->import($fitur, $validated['jenis'] ?? null);

        return response()->json([
            'message' => 'Impor OpenStreetMap selesai.',
            'data' => $stats,
        ]);
    }
}
