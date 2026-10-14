"""
gmaps_scraper.py
================
Scraper Google Maps menggunakan Playwright (headless Chromium).

Cara pakai:
  1. Install dependensi:
       pip install playwright
       playwright install chromium

  2. Jalankan:
       # Cari berdasarkan query teks
       python gmaps_scraper.py --query "warteg tangerang selatan" --maks 20

       # Atau dari file daftar query (satu query per baris)
       python gmaps_scraper.py --file daftar_tempat.txt --maks 50

       # Tentukan output
       python gmaps_scraper.py --query "kafe serpong" --output hasil_kafe.json

  3. Output: file JSON siap import ke Laravel (php artisan gmaps:import hasil.json)

Catatan:
  - Jalankan tanpa --headless untuk melihat browser bekerja (debugging)
  - Tambahkan --jeda 3 untuk memperlambat (default 2 detik antar tempat)
"""

import argparse
import json
import re
import sys
import time
import random
from dataclasses import asdict, dataclass, field
from datetime import datetime
from pathlib import Path
from typing import Optional

try:
    from playwright.sync_api import sync_playwright, Page, TimeoutError as PWTimeout
except ImportError:
    print("[ERROR] Playwright belum terinstall. Jalankan:")
    print("  pip install playwright")
    print("  playwright install chromium")
    sys.exit(1)


# ─────────────────────────── Data Model ───────────────────────────

@dataclass
class HasilScraping:
    """Struktur data satu tempat — cocok dengan schema DB project."""
    nama: str = ""
    alamat: str = ""
    latitude: Optional[float] = None
    longitude: Optional[float] = None
    telepon: Optional[str] = None
    website: Optional[str] = None
    rating: Optional[float] = None
    rating_kount: Optional[int] = None
    jam_buka_teks: Optional[str] = None
    jam_per_hari: Optional[dict] = None
    rentang_harga: Optional[str] = None
    kategori: Optional[str] = None
    fasilitas: list = field(default_factory=list)
    foto_url: list = field(default_factory=list)
    google_maps_url: str = ""
    diambil_pada: str = field(default_factory=lambda: datetime.now().isoformat())


# ─────────────────────────── Helpers ───────────────────────────────

def _teks(page: Page, selector: str, timeout: int = 3000) -> Optional[str]:
    try:
        el = page.wait_for_selector(selector, timeout=timeout)
        return el.inner_text().strip() if el else None
    except PWTimeout:
        return None


def _jeda(detik_min: float = 1.5, detik_max: float = 3.5) -> None:
    time.sleep(random.uniform(detik_min, detik_max))


def _koordinat_dari_url(url: str) -> tuple:
    m = re.search(r'/@(-?\d+\.\d+),(-?\d+\.\d+)', url)
    if m:
        return float(m.group(1)), float(m.group(2))
    return None, None


def _parse_rating_kount(teks: str) -> Optional[int]:
    angka = re.sub(r'[^\d]', '', teks)
    return int(angka) if angka else None


def _bersihkan_telepon(raw: str) -> str:
    return re.sub(r'[^\d+\-\s()]', '', raw).strip()


# ─────────────────────────── Scraper Core ──────────────────────────

class GMapsScraper:
    GMAPS_SEARCH = "https://www.google.com/maps/search/{query}/"

    def __init__(self, headless: bool = True, jeda: float = 2.0):
        self.headless = headless
        self.jeda = jeda
        self._pw = None
        self._browser = None
        self._ctx = None
        self._page: Optional[Page] = None

    def __enter__(self):
        self._pw = sync_playwright().start()
        self._browser = self._pw.chromium.launch(
            headless=self.headless,
            args=[
                "--no-sandbox",
                "--disable-blink-features=AutomationControlled",
            ],
        )
        self._ctx = self._browser.new_context(
            locale="id-ID",
            timezone_id="Asia/Jakarta",
            user_agent=(
                "Mozilla/5.0 (Windows NT 10.0; Win64; x64) "
                "AppleWebKit/537.36 (KHTML, like Gecko) "
                "Chrome/124.0.0.0 Safari/537.36"
            ),
            viewport={"width": 1280, "height": 900},
        )
        self._page = self._ctx.new_page()
        return self

    def __exit__(self, *_):
        if self._browser:
            self._browser.close()
        if self._pw:
            self._pw.stop()

    def cari(self, query: str, maks: int = 10) -> list:
        """Buka halaman pencarian GMaps, return daftar URL detail tempat."""
        print(f"  Mencari: {query!r} (maks {maks} tempat)")
        url = self.GMAPS_SEARCH.format(query=query.replace(" ", "+"))
        self._page.goto(url, wait_until="load", timeout=60_000)
        # Tunggu panel hasil muncul (GMaps render lazily setelah 'load')
        try:
            self._page.wait_for_selector('div[role="feed"], a[href*="/maps/place/"]', timeout=15_000)
        except PWTimeout:
            pass
        _jeda(2, 4)
        self._tutup_consent()

        hasil_url = []
        sebelumnya = 0

        for _ in range(max(1, maks // 5 + 2)):
            links = self._page.query_selector_all('a[href*="/maps/place/"]')
            hrefs = list({
                el.get_attribute("href")
                for el in links
                if el.get_attribute("href")
            })
            hasil_url = hrefs[:maks]

            if len(hasil_url) >= maks:
                break

            try:
                panel = self._page.query_selector('div[role="feed"]')
                if panel:
                    panel.evaluate("el => el.scrollBy(0, 800)")
                    _jeda(1.5, 2.5)
            except Exception:
                pass

            if len(hasil_url) == sebelumnya:
                break
            sebelumnya = len(hasil_url)

        print(f"  Ditemukan {len(hasil_url)} URL tempat")
        return hasil_url[:maks]

    def scrape_detail(self, url: str) -> Optional[HasilScraping]:
        """Buka URL detail satu tempat di GMaps, ekstrak semua info."""
        try:
            self._page.goto(url, wait_until="load", timeout=60_000)
            # Tunggu konten detail muncul sebelum ekstrak
            try:
                self._page.wait_for_selector('h1, div.F7nice', timeout=15_000)
            except PWTimeout:
                pass
            _jeda(2, 3)
            self._tutup_consent()
        except Exception as e:
            print(f"    Gagal load: {e}")
            return None

        hasil = HasilScraping(google_maps_url=self._page.url)

        # Nama
        hasil.nama = (
            _teks(self._page, 'h1[class*="DUwDvf"]', 5000)
            or _teks(self._page, 'h1', 2000)
            or ""
        )

        # Kategori
        kategori_el = self._page.query_selector('button[jsaction*="category"]')
        if not kategori_el:
            kategori_el = self._page.query_selector('span.DkEaL')
        if kategori_el:
            hasil.kategori = kategori_el.inner_text().strip()

        # Harga
        harga_el = self._page.query_selector('span[aria-label*="Harga"]')
        if harga_el:
            hasil.rentang_harga = (harga_el.get_attribute("aria-label") or "").replace("Harga: ", "")

        # Rating
        rating_teks = _teks(self._page, 'div.F7nice span[aria-hidden="true"]', 3000)
        if rating_teks:
            try:
                hasil.rating = float(rating_teks.replace(",", "."))
            except ValueError:
                pass

        kount_el = self._page.query_selector('div.F7nice span[aria-label*="ulasan"]')
        if kount_el:
            label = kount_el.get_attribute("aria-label") or ""
            hasil.rating_kount = _parse_rating_kount(label)

        # Alamat
        alamat_el = self._page.query_selector('button[data-item-id="address"]')
        if alamat_el:
            hasil.alamat = (alamat_el.get_attribute("aria-label") or "").replace("Alamat: ", "").strip()

        # Telepon
        tel_el = self._page.query_selector('button[data-item-id*="phone:tel"]')
        if tel_el:
            raw_tel = tel_el.get_attribute("aria-label") or ""
            hasil.telepon = _bersihkan_telepon(raw_tel.replace("Telepon: ", ""))

        # Website
        web_el = self._page.query_selector('a[data-item-id="authority"]')
        if web_el:
            hasil.website = web_el.get_attribute("href")

        # Jam buka
        hasil.jam_buka_teks, hasil.jam_per_hari = self._ambil_jam()

        # Koordinat dari URL
        hasil.latitude, hasil.longitude = _koordinat_dari_url(self._page.url)

        # Foto
        hasil.foto_url = self._ambil_foto(maks=5)

        # Fasilitas
        hasil.fasilitas = self._ambil_fasilitas()

        return hasil

    def _ambil_jam(self) -> tuple:
        jam_per_hari = {}
        jam_teks = None
        try:
            tombol = self._page.query_selector('button[jsaction*="openhours"]')
            if not tombol:
                tombol = self._page.query_selector('div[aria-label*="jam buka"]')
            if tombol:
                jam_teks = tombol.inner_text().strip().split("\n")[0]
                tombol.click()
                _jeda(1, 2)
                baris = self._page.query_selector_all('table.eK4R0e tr')
                for baris_el in baris:
                    td_list = baris_el.query_selector_all("td, th")
                    if len(td_list) >= 2:
                        hari = td_list[0].inner_text().strip()
                        jam = td_list[1].inner_text().strip()
                        if hari:
                            jam_per_hari[hari] = jam
        except Exception:
            pass
        return jam_teks, jam_per_hari if jam_per_hari else None

    def _ambil_foto(self, maks: int = 5) -> list:
        urls = []
        try:
            imgs = self._page.query_selector_all(
                'button[jsaction*="heroHeaderImage"] img, div[data-photo-index] img'
            )
            for img in imgs[:maks]:
                src = img.get_attribute("src") or ""
                if src.startswith("https://lh") or "googleusercontent" in src:
                    src_hd = re.sub(r'=w\d+-h\d+', '=w800-h600', src)
                    urls.append(src_hd)
        except Exception:
            pass
        return urls

    def _ambil_fasilitas(self) -> list:
        """
        Ambil daftar fasilitas/amenitas dari halaman detail GMaps.
        GMaps menampilkan amenitas sebagai daftar dengan ikon checkmark/silang
        di bagian 'Fasilitas & layanan' atau 'Dari bisnis'.

        Strategi berlapis:
          1. Scroll ke bawah untuk reveal section fasilitas
          2. Cari section amenitas lewat heading 'Fasilitas', 'Dari bisnis', 'Amenities'
          3. Ekstrak teks item yang ter-centang (memiliki ikon checklist)
          4. Fallback: ambil dari aria-label span dalam section amenitas
        """
        hasil = []
        FILTER_RATING = re.compile(r'^\d[,\.]\d\s*(bintang|stars?)$', re.IGNORECASE)
        FILTER_PENDEK = re.compile(r'^\d+$')  # jangan simpan angka saja

        try:
            # Scroll perlahan ke bawah untuk trigger lazy-load section fasilitas
            for offset in [400, 800, 1200]:
                self._page.evaluate(f"window.scrollBy(0, {offset})")
                _jeda(0.5, 1.0)

            # ── Strategi 1: Cari berdasarkan heading section ──────────────
            heading_selectors = [
                # Heading "Fasilitas & layanan" atau "Dari bisnis"
                'h2.iL3Qke',
                'div[aria-label*="Fasilitas"]',
                'div[aria-label*="Ameniti"]',
                'div[aria-label*="amenities"]',
                'span.iL3Qke',
            ]

            for sel in heading_selectors:
                headings = self._page.query_selector_all(sel)
                for heading in headings:
                    teks_heading = heading.inner_text().strip().lower()
                    if any(k in teks_heading for k in ['fasilitas', 'layanan', 'dari bisnis', 'ameniti', 'highlight']):
                        # Ambil parent container, lalu cari semua item di dalamnya
                        parent = self._page.evaluate(
                            '(el) => el.closest("div.iP2t7d, div.aSfjle, section, div.m6QErb")',
                            heading
                        )
                        if parent:
                            item_els = heading.evaluate(
                                '''(el) => {
                                    const section = el.closest("div.iP2t7d, div.aSfjle, section") 
                                        || el.parentElement?.parentElement;
                                    if (!section) return [];
                                    return Array.from(section.querySelectorAll(
                                        "span.fontBodyMedium, li, div.fontBodyMedium, span[aria-label]"
                                    )).map(e => e.innerText || e.getAttribute("aria-label") || "")
                                     .filter(t => t.trim().length > 0);
                                }'''
                            )
                            if item_els:
                                hasil.extend(item_els)

            # ── Strategi 2: Selector langsung item fasilitas GMaps ────────
            if not hasil:
                # Item fasilitas yang aktif (ada checkmark) vs tidak tersedia (ada X)
                # GMaps memakai class berbeda untuk tersedia vs tidak tersedia
                selectors_fasilitas = [
                    # Item yang TERSEDIA (centang hijau) - class AeaXub
                    'div.AeaXub span.fontBodyMedium',
                    # Alternatif: li di dalam section amenitas
                    'ul.mlBbDb li span',
                    'ul.ZPV5Id li',
                    # Section "Aksesibilitas", "Layanan", dll
                    'div.LTs0Rc span.fontBodyMedium',
                    # Chip/badge fasilitas
                    'div.e2moi span.uEubGf',
                ]
                for sel in selectors_fasilitas:
                    els = self._page.query_selector_all(sel)
                    for el in els:
                        teks = el.inner_text().strip()
                        if teks and 3 < len(teks) < 80:
                            hasil.append(teks)
                    if hasil:
                        break

            # ── Strategi 3: aria-label pada span di section amenitas ──────
            if not hasil:
                # Cari semua span dengan aria-label yang kontekstual
                els = self._page.query_selector_all(
                    'div.iP2t7d span[aria-label], div.aSfjle span[aria-label]'
                )
                for el in els:
                    label = el.get_attribute("aria-label") or ""
                    if label and 3 < len(label) < 80:
                        hasil.append(label)

        except Exception:
            pass

        # Bersihkan: filter rating bintang dan angka saja, deduplicate
        bersih = []
        seen = set()
        for item in hasil:
            item = item.strip()
            if not item:
                continue
            if FILTER_RATING.match(item):
                continue
            if FILTER_PENDEK.match(item):
                continue
            if item.lower() in seen:
                continue
            seen.add(item.lower())
            bersih.append(item)

        return bersih

    def _tutup_consent(self) -> None:
        try:
            for selector in [
                'button[aria-label*="Accept all"]',
                'button[aria-label*="Setuju"]',
                'form[action*="consent"] button',
                '#L2AGLb',
            ]:
                btn = self._page.query_selector(selector)
                if btn:
                    btn.click()
                    _jeda(1, 2)
                    break
        except Exception:
            pass


# ─────────────────────────── Main ──────────────────────────────────

def jalankan(args: argparse.Namespace) -> None:
    queries = []

    if args.query:
        queries = [args.query]
    elif args.file:
        path = Path(args.file)
        if not path.exists():
            print(f"[ERROR] File tidak ditemukan: {args.file}")
            sys.exit(1)
        queries = [
            line.strip()
            for line in path.read_text(encoding="utf-8").splitlines()
            if line.strip() and not line.startswith("#")
        ]
    else:
        print("[ERROR] Harus pakai --query atau --file")
        sys.exit(1)

    output_path = Path(args.output)
    semua_hasil = []

    # Resume mode: lanjut dari file yang sudah ada
    if output_path.exists():
        try:
            semua_hasil = json.loads(output_path.read_text(encoding="utf-8"))
            print(f"Melanjutkan dari {len(semua_hasil)} data sebelumnya")
        except json.JSONDecodeError:
            pass

    with GMapsScraper(headless=not args.show_browser, jeda=args.jeda) as scraper:
        for i, query in enumerate(queries, 1):
            print(f"\n[{i}/{len(queries)}] Query: {query!r}")
            urls = scraper.cari(query, maks=args.maks)

            for j, url in enumerate(urls, 1):
                print(f"  [{j}/{len(urls)}] Scraping: {url[:80]}...")
                hasil = scraper.scrape_detail(url)

                if hasil and hasil.nama:
                    semua_hasil.append(asdict(hasil))
                    print(f"  OK: {hasil.nama} — {hasil.alamat[:50]}")
                else:
                    print(f"  Dilewati: data tidak lengkap")

                # Auto-save setiap tempat
                output_path.write_text(
                    json.dumps(semua_hasil, ensure_ascii=False, indent=2),
                    encoding="utf-8",
                )

                _jeda(args.jeda - 0.5, args.jeda + 1.5)

    print(f"\nSelesai! Total {len(semua_hasil)} tempat disimpan ke {output_path}")


def main() -> None:
    parser = argparse.ArgumentParser(
        description="Scraper Google Maps -> JSON (untuk import ke Laravel Tangselia)"
    )
    parser.add_argument("--query", "-q", help='Query pencarian, mis. "kafe serpong"')
    parser.add_argument("--file", "-f", help="File teks berisi daftar query (1 per baris)")
    parser.add_argument("--maks", "-m", type=int, default=10, help="Maks hasil per query (default: 10)")
    parser.add_argument("--output", "-o", default="hasil_scraping.json", help="File output JSON")
    parser.add_argument("--show-browser", action="store_true", help="Tampilkan jendela browser (debugging)")
    parser.add_argument("--jeda", type=float, default=2.0, help="Jeda minimum antar request (detik)")

    args = parser.parse_args()
    jalankan(args)


if __name__ == "__main__":
    main()
