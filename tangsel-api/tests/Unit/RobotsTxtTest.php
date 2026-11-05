<?php

namespace Tests\Unit;

use App\Services\RobotsTxt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RobotsTxtTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_pola_wildcard_dan_penanda_akhir_dibaca_dengan_benar(): void
    {
        Http::fake([
            'https://glints.com/robots.txt' => Http::response(
                "User-agent: *\n"
                ."Disallow: */opportunities/jobs/explore?*\n"
                ."Disallow: */opportunities/jobs/recommended$\n"
            ),
        ]);

        $robots = new RobotsTxt;

        $this->assertFalse($robots->allows('https://glints.com/id/opportunities/jobs/explore?keyword=administrasi'));
        $this->assertFalse($robots->allows('https://glints.com/id/opportunities/jobs/recommended'));
        $this->assertTrue($robots->allows('https://glints.com/id/opportunities/jobs/recommended/tambahan'));
        $this->assertTrue($robots->allows('https://glints.com/id/job-location/indonesia/banten/tangerang-selatan'));
    }

    public function test_allow_menang_bila_polanya_lebih_panjang(): void
    {
        Http::fake([
            'https://contoh.test/robots.txt' => Http::response(
                "User-agent: *\n"
                ."Disallow: /lowongan/\n"
                ."Allow: /lowongan/publik/\n"
            ),
        ]);

        $robots = new RobotsTxt;

        $this->assertFalse($robots->allows('https://contoh.test/lowongan/rahasia'));
        $this->assertTrue($robots->allows('https://contoh.test/lowongan/publik/beranda'));
    }

    public function test_aturan_khusus_agen_mengalahkan_aturan_wildcard(): void
    {
        Http::fake([
            'https://contoh.test/robots.txt' => Http::response(
                "User-agent: *\n"
                ."Disallow: /\n"
                ."\n"
                ."User-agent: TANGSEL-LIA-Bot\n"
                ."Allow: /\n"
            ),
        ]);

        $robots = new RobotsTxt;

        $this->assertTrue($robots->allows('https://contoh.test/halaman'));
    }
}
