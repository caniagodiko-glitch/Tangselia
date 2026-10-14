<?php

namespace Tests\Feature;

use App\Models\Kecamatan;
use App\Models\LayananKesehatan;
use App\Services\WikipediaFetcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WikipediaFetcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_mencocokkan_rs_dan_mengisi_alamat_serta_deskripsi(): void
    {
        $kecamatan = Kecamatan::factory()->create(['nama' => 'Ciputat Timur', 'slug' => 'ciputat-timur']);

        $hermina = LayananKesehatan::factory()->create([
            'jenis' => 'rumah_sakit',
            'nama' => 'RS Hermina Ciputat',
            'slug' => 'rs-hermina-ciputat',
            'alamat' => 'Kecamatan Ciputat Timur, Kota Tangerang Selatan',
            'kecamatan_id' => $kecamatan->id,
            'deskripsi_sumber' => 'osm',
        ]);

        // Tidak ada di DB — harus dilewati, bukan salah cocok.
        $this->fakeWikipedia();

        $stats = app(WikipediaFetcher::class)->sinkronkanRumahSakit();
        $hermina->refresh();

        $this->assertSame(1, $stats['dicocokkan']);
        $this->assertSame(1, $stats['alamat_terisi']);
        $this->assertSame(1, $stats['deskripsi_terisi']);
        $this->assertStringContainsString('Jalan Kertamukti', (string) $hermina->alamat);
        $this->assertSame('wikipedia', $hermina->deskripsi_sumber);
        $this->assertStringContainsString('Rumah Sakit Hermina Ciputat adalah', (string) $hermina->deskripsi);
        $this->assertContains('Wikipedia bahasa Indonesia', array_column($hermina->atribusi, 'sumber'));
        $this->assertDatabaseCount('layanan_kesehatan', 1);
    }

    public function test_nama_ambigu_dilewati_demi_keakuratan(): void
    {
        $kecamatan = Kecamatan::factory()->create(['nama' => 'Ciputat', 'slug' => 'ciputat']);

        foreach (['RS Sari Asih', 'RS Sari Asih Bintaro'] as $index => $nama) {
            LayananKesehatan::factory()->create([
                'jenis' => 'rumah_sakit',
                'nama' => $nama,
                'slug' => 'sari-asih-'.$index,
                'alamat' => 'Kecamatan Ciputat, Kota Tangerang Selatan',
                'kecamatan_id' => $kecamatan->id,
            ]);
        }

        Http::fake(function () {
            $wikiteks = "{| class=\"wikitable\"\n!№\n!Kode\n!Nama Rumah Sakit\n!Jenis\n!Tipe\n!Alamat\n|-\n"
                ."|1.\n|3674005\n|[[RS Sari Asih Ciputat]]\n|RS\n|B\n|Jalan Sasak Tinggi, Kec. Ciputat, Kota Tangerang Selatan\n|}";

            return Http::response(['parse' => ['wikitext' => ['*' => $wikiteks]]]);
        });

        $stats = app(WikipediaFetcher::class)->sinkronkanRumahSakit();

        $this->assertSame(0, $stats['dicocokkan']);
        $this->assertSame(0, $stats['alamat_terisi']);
    }

    private function fakeWikipedia(): void
    {
        Http::fake(function ($request) {
            if (str_contains($request->url(), 'action=parse')) {
                $wikiteks = "{| class=\"wikitable\"\n!№\n!Kode\n!Nama Rumah Sakit\n!Jenis\n!Tipe\n!Alamat\n|-\n"
                    ."|1.\n|3674007\n|[[RS Hermina Ciputat]]\n|RS\n|C\n"
                    ."|Jalan Kertamukti №2, [[Cireundeu, Ciputat Timur, Tangerang Selatan|Cireundeu]], Kec. Ciputat Timur, Kota Tangerang Selatan, Banten 15419\n|-\n"
                    ."|2.\n|3674009\n|[[RS Aria Sentra Medika]]\n|RS\n|C\n"
                    .'|Jalan Aria Putra №9, Kec. Pamulang, Kota Tangerang Selatan, Banten 15415\n|}';

                return Http::response(['parse' => ['wikitext' => ['*' => $wikiteks]]]);
            }

            return Http::response(['query' => ['pages' => [
                '1' => [
                    'pageid' => 1,
                    'title' => 'RS Hermina Ciputat',
                    'extract' => 'Rumah Sakit Hermina Ciputat adalah rumah sakit swasta di Ciputat, Tangerang Selatan yang melayani beragam layanan medis untuk warga.',
                ],
            ]]]);
        });
    }
}
