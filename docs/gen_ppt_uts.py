#!/usr/bin/env python3
"""Generator deck presentasi UTS TANGSEL LIA (15 slide).

Jalankan dari root repo:

    python docs/gen_ppt_uts.py

Hasil: UTS_TANGSEL_LIA.pptx di root repo.
Desain mengikuti deck lama: header biru, kartu abu muda, footer bernomor.
"""

from pptx import Presentation
from pptx.dml.color import RGBColor
from pptx.enum.shapes import MSO_SHAPE
from pptx.enum.text import MSO_ANCHOR, MSO_AUTO_SIZE, PP_ALIGN
from pptx.util import Inches, Pt

# ---------------------------------------------------------------- palet & ukuran
W, H = 13.333, 7.5
NAVY = RGBColor(0x0D, 0x47, 0xA1)
BLUE2 = RGBColor(0x15, 0x65, 0xC0)
ACCENT = RGBColor(0x42, 0xA5, 0xF5)
LIGHT = RGBColor(0xF5, 0xF5, 0xF5)
PALE = RGBColor(0xE3, 0xF2, 0xFD)
GREEN = RGBColor(0xE8, 0xF5, 0xE9)
RED = RGBColor(0xFF, 0xEB, 0xEE)
WHITE = RGBColor(0xFF, 0xFF, 0xFF)
TEXT = RGBColor(0x21, 0x21, 0x21)
MUTED = RGBColor(0x55, 0x55, 0x55)
GREEN_TX = RGBColor(0x1B, 0x5E, 0x20)
RED_TX = RGBColor(0xB7, 0x1C, 0x1C)
BORDER = RGBColor(0xE0, 0xE0, 0xE0)

ALIGN = {
    "l": PP_ALIGN.LEFT,
    "c": PP_ALIGN.CENTER,
    "r": PP_ALIGN.RIGHT,
    "j": PP_ALIGN.JUSTIFY,
}
ANCHOR = {
    "t": MSO_ANCHOR.TOP,
    "m": MSO_ANCHOR.MIDDLE,
    "b": MSO_ANCHOR.BOTTOM,
}


def rect(slide, x, y, w, h, fill=None, line=None, line_w=0.75):
    sh = slide.shapes.add_shape(
        MSO_SHAPE.RECTANGLE, Inches(x), Inches(y), Inches(w), Inches(h)
    )
    if fill is None:
        sh.fill.background()
    else:
        sh.fill.solid()
        sh.fill.fore_color.rgb = fill
    if line is None:
        sh.line.fill.background()
    else:
        sh.line.color.rgb = line
        sh.line.width = Pt(line_w)
    sh.shadow.inherit = False
    return sh


def txt(
    slide,
    x,
    y,
    w,
    h,
    paras,
    anchor="t",
    align="l",
    ml=0.07,
    mr=0.07,
    mt=0.03,
    mb=0.03,
):
    tb = slide.shapes.add_textbox(Inches(x), Inches(y), Inches(w), Inches(h))
    tf = tb.text_frame
    tf.word_wrap = True
    tf.auto_size = MSO_AUTO_SIZE.NONE
    tf.margin_left = Inches(ml)
    tf.margin_right = Inches(mr)
    tf.margin_top = Inches(mt)
    tf.margin_bottom = Inches(mb)
    tf.vertical_anchor = ANCHOR[anchor]

    first = True
    for p in paras:
        lines = [None] if "runs" in p else p["t"].split("\n")
        for line in lines:
            para = tf.paragraphs[0] if first else tf.add_paragraph()
            first = False
            para.alignment = ALIGN[p.get("align", align)]
            para.line_spacing = p.get("ls", 1.0)
            para.space_before = Pt(p.get("sb", 0))
            para.space_after = Pt(p.get("sa", 0))
            if "runs" in p:
                for rt, ro in p["runs"]:
                    run = para.add_run()
                    run.text = rt
                    run.font.size = Pt(ro.get("sz", p.get("sz", 12)))
                    run.font.bold = ro.get("b", p.get("b", False))
                    run.font.italic = ro.get("i", p.get("i", False))
                    run.font.color.rgb = ro.get("c", p.get("c", TEXT))
            else:
                run = para.add_run()
                run.text = line
                run.font.size = Pt(p.get("sz", 12))
                run.font.bold = p.get("b", False)
                run.font.italic = p.get("i", False)
                run.font.color.rgb = p.get("c", TEXT)
    return tb


def new_slide(prs):
    s = prs.slides.add_slide(prs.slide_layouts[6])
    rect(s, 0, 0, W, H, WHITE)
    return s


def chrome(s, title, sub, page):
    rect(s, 0, 0, W, 1.3, NAVY)
    txt(
        s,
        0.4,
        0.10,
        12.3,
        0.72,
        [{"t": title, "sz": 28, "b": True, "c": WHITE}],
        anchor="m",
    )
    if sub:
        txt(
            s,
            0.4,
            0.79,
            12.3,
            0.42,
            [{"t": sub, "sz": 13.5, "c": ACCENT}],
            anchor="m",
        )
    rect(s, 0, 1.3, W, 0.06, ACCENT)
    rect(s, 0, 7.1, W, 0.4, NAVY)
    txt(
        s,
        0.3,
        7.1,
        10.0,
        0.4,
        [{"t": "TANGSEL LIA — UTS Tengah Semester", "sz": 10, "c": WHITE}],
        anchor="m",
    )
    txt(
        s,
        12.3,
        7.1,
        0.8,
        0.4,
        [{"t": "%d/15" % page, "sz": 10, "c": WHITE, "align": "r"}],
        anchor="m",
    )


def notes(slide, text):
    slide.notes_slide.notes_text_frame.text = text


def flow(slide, steps, y, box_w, box_h, gap, start=None, main_sz=9.5, sub_sz=7.5):
    """Deretan kotak navy dengan panah di antaranya."""
    total = len(steps) * box_w + (len(steps) - 1) * gap
    x = (W - total) / 2 if start is None else start
    for i, (main, sub) in enumerate(steps):
        rect(slide, x, y, box_w, box_h, NAVY)
        paras = [{"t": main, "sz": main_sz, "b": True, "c": WHITE, "align": "c"}]
        if sub:
            paras.append({"t": sub, "sz": sub_sz, "c": PALE, "align": "c", "sb": 2})
        txt(slide, x + 0.02, y + 0.04, box_w - 0.04, box_h - 0.08, paras, anchor="m")
        if i < len(steps) - 1:
            txt(
                slide,
                x + box_w,
                y,
                gap,
                box_h,
                [{"t": "→", "sz": 17, "b": True, "c": ACCENT, "align": "c"}],
                anchor="m",
                ml=0,
                mr=0,
            )
        x += box_w + gap


def simple_table(
    slide,
    x,
    y,
    widths,
    rows,
    header_h=0.45,
    row_h=0.5,
    body_sz=10.5,
    head_sz=11,
    aligns=None,
    first_col_bold=False,
):
    aligns = aligns or ["l"] * len(widths)
    cy = y
    for ri, row in enumerate(rows):
        rh = header_h if ri == 0 else row_h
        cx = x
        for ci, cell in enumerate(row):
            if ri == 0:
                fill, tx, bold = NAVY, WHITE, True
            else:
                fill = LIGHT if ri % 2 == 1 else WHITE
                tx = TEXT
                bold = first_col_bold and ci == 0
            rect(slide, cx, cy, widths[ci], rh, fill, BORDER, 0.5)
            txt(
                slide,
                cx + 0.02,
                cy + 0.02,
                widths[ci] - 0.04,
                rh - 0.04,
                [
                    {
                        "t": cell,
                        "sz": head_sz if ri == 0 else body_sz,
                        "b": bold,
                        "c": tx,
                        "align": aligns[ci],
                        "ls": 1.02,
                    }
                ],
                anchor="m",
            )
            cx += widths[ci]
        cy += rh


# --------------------------------------------------------------------- slide 1
def slide_cover(prs):
    s = new_slide(prs)
    rect(s, 0, 0, W, H, NAVY)
    rect(s, 0, 4.5, W, 3.0, BLUE2)
    rect(s, 0, 4.4, W, 0.08, ACCENT)
    txt(
        s,
        1.0,
        0.6,
        11.0,
        1.4,
        [{"t": "TANGSEL LIA", "sz": 54, "b": True, "c": WHITE}],
        anchor="m",
    )
    txt(
        s,
        1.0,
        1.9,
        11.0,
        0.7,
        [{"t": "Platform Informasi Lokal Tangerang Selatan", "sz": 22, "c": ACCENT}],
        anchor="m",
    )
    rect(s, 4.0, 2.75, 5.33, 0.05, ACCENT)
    txt(
        s,
        1.0,
        3.0,
        11.0,
        0.5,
        [{"t": "Presentasi Tengah Semester — Tugas Akhir", "sz": 16, "c": WHITE}],
        anchor="m",
    )
    txt(
        s,
        3.5,
        4.8,
        7.0,
        2.0,
        [
            {"t": "Nama    :  [Nama Lengkap]", "sz": 16, "c": WHITE, "sa": 6},
            {"t": "NIM       :  [NIM]", "sz": 16, "c": WHITE, "sa": 6},
            {"t": "Prodi     :  Teknik Informatika", "sz": 16, "c": WHITE, "sa": 6},
            {"t": "Pembimbing :  [Nama Dosen]", "sz": 16, "c": WHITE},
        ],
    )
    txt(
        s,
        1.0,
        6.9,
        11.0,
        0.4,
        [{"t": "2026", "sz": 14, "c": ACCENT}],
        anchor="m",
    )
    notes(s, "Salam pembuka, perkenalkan diri, lalu sampaikan bahwa presentasi ini menunjukkan progres sistem.")


# --------------------------------------------------------------------- slide 2
def slide_latar(prs):
    s = new_slide(prs)
    chrome(s, "Latar Belakang", "Mengapa TANGSEL LIA Dibutuhkan?", 2)
    cards = [
        (
            "🗺️  Informasi tersebar",
            "Berita di media nasional, lowongan di job board umum, tempat hanya di Google Maps — tidak ada yang khusus Tangsel.",
        ),
        (
            "🔍  Tidak ada agregator lokal",
            "Belum ada platform yang menggabungkan berita, lowongan, dan direktori tempat dalam satu aplikasi.",
        ),
        (
            "📱  Kebutuhan warga",
            "Warga Tangsel butuh akses cepat ke info lokal yang terpercaya, terfilter, dan mudah ditemukan.",
        ),
    ]
    for i, (title, body) in enumerate(cards):
        x = 0.5 + i * 4.15
        rect(s, x, 1.6, 3.8, 3.5, LIGHT, ACCENT)
        txt(s, x + 0.15, 1.72, 3.5, 0.65, [{"t": title, "sz": 17, "b": True, "c": NAVY}])
        txt(s, x + 0.15, 2.4, 3.5, 2.5, [{"t": body, "sz": 12.5, "c": TEXT, "ls": 1.15}])
    rect(s, 0.5, 5.35, 12.33, 0.08, ACCENT)
    txt(
        s,
        0.5,
        5.5,
        12.33,
        0.9,
        [
            {
                "t": "✅  Solusi: Platform TANGSEL LIA — satu tempat untuk berita kota, direktori tempat, dan lowongan kerja Tangerang Selatan.",
                "sz": 15,
                "b": True,
                "c": NAVY,
                "ls": 1.1,
            }
        ],
    )
    notes(s, "Tekankan tiga masalah ini muncul bersamaan, sehingga solusinya harus menyatukan ketiganya.")


# --------------------------------------------------------------------- slide 3
def slide_rumusan(prs):
    s = new_slide(prs)
    chrome(s, "Rumusan Masalah & Tujuan", "Apa yang ingin diselesaikan dan apa targetnya", 3)

    rect(s, 0.4, 1.5, 6.0, 4.9, LIGHT, ACCENT)
    txt(s, 0.55, 1.62, 5.7, 0.5, [{"t": "Rumusan Masalah", "sz": 17, "b": True, "c": NAVY}])
    rumusan = [
        "Bagaimana membangun sistem agregasi berita lokal Tangerang Selatan secara otomatis?",
        "Bagaimana membangun direktori tempat yang akurat secara geografis untuk wilayah Tangsel?",
        "Bagaimana menyediakan informasi lowongan kerja lokal yang akurat dan bertautan langsung ke sumber resmi secara otomatis?",
    ]
    paras = []
    for i, t in enumerate(rumusan, 1):
        paras.append(
            {
                "runs": [
                    ("%d.  " % i, {"b": True, "c": ACCENT, "sz": 13.5}),
                    (t, {"c": TEXT, "sz": 13.5}),
                ],
                "sa": 10,
                "ls": 1.1,
            }
        )
    txt(s, 0.6, 2.2, 5.6, 3.9, paras)

    rect(s, 6.8, 1.5, 6.0, 4.9, PALE, ACCENT)
    txt(s, 6.95, 1.62, 5.7, 0.5, [{"t": "Tujuan Penelitian", "sz": 17, "b": True, "c": NAVY}])
    tujuan = [
        "Membangun platform web yang mengintegrasikan berita, lowongan, dan direktori tempat Tangsel.",
        "Menyediakan informasi yang terfilter dan relevan khusus untuk warga Tangerang Selatan.",
        "Membangun pipeline scraping JSON-LD yang menayangkan lowongan relevan secara otomatis, dengan kurasi manual sebagai cadangan.",
    ]
    paras = []
    for t in tujuan:
        paras.append(
            {
                "runs": [
                    ("✅  ", {"sz": 13}),
                    (t, {"c": TEXT, "sz": 13.5}),
                ],
                "sa": 10,
                "ls": 1.1,
            }
        )
    txt(s, 7.0, 2.2, 5.6, 3.9, paras)
    notes(s, "Rumusan ketiga sengaja menekankan akurasi dan tautan langsung, bukan sekadar jumlah lowongan.")


# --------------------------------------------------------------------- slide 4
def slide_lingkup(prs):
    s = new_slide(prs)
    chrome(s, "Ruang Lingkup & Batasan Sistem", "Agar pengerjaan tetap fokus dan terukur", 4)

    rect(s, 0.4, 1.5, 5.9, 5.0, GREEN, ACCENT)
    txt(s, 0.6, 1.62, 5.5, 0.5, [{"t": "Yang Dicakup", "sz": 17, "b": True, "c": GREEN_TX}])
    masuk = [
        "Cakupan wilayah: 7 kecamatan Tangerang Selatan",
        "Berita lokal: agregasi, filter wilayah, ringkasan AI",
        "Lowongan kerja & magang: scraping JSON-LD",
        "Direktori: kuliner, olahraga, kesehatan",
        "Ulasan & rating warga, serta dasbor admin",
    ]
    txt(
        s,
        0.6,
        2.2,
        5.5,
        4.0,
        [{"t": "✅  " + t, "sz": 12.5, "c": TEXT, "sa": 11, "ls": 1.1} for t in masuk],
    )

    rect(s, 6.9, 1.5, 5.9, 5.0, RED, ACCENT)
    txt(s, 7.1, 1.62, 5.5, 0.5, [{"t": "Yang Tidak Dicakup", "sz": 17, "b": True, "c": RED_TX}])
    luar = [
        "Bukan aplikasi navigasi atau rute perjalanan",
        "Tidak menyimpan isi penuh artikel berita",
        "Tidak melayani pesan-antar atau reservasi",
        "Ulasan hanya untuk pengguna yang sudah login",
        "Data tempat hanya wilayah Tangerang Selatan",
    ]
    txt(
        s,
        7.1,
        2.2,
        5.5,
        4.0,
        [{"t": "❌  " + t, "sz": 12.5, "c": TEXT, "sa": 11, "ls": 1.1} for t in luar],
    )
    notes(s, "Batasan ini yang menjawab pertanyaan 'kenapa tidak sekalian jadi aplikasi maps'.")


# --------------------------------------------------------------------- slide 5
def slide_teknologi(prs):
    s = new_slide(prs)
    chrome(s, "Teknologi & Teori yang Digunakan", "Perangkat yang dipakai dan dasar teorinya", 5)

    rows = [
        ("Backend", "PHP 8.4 + Laravel 13"),
        ("Frontend", "Next.js 16 + React 19 + TypeScript"),
        ("Database", "MySQL 8"),
        ("Scraping", "Playwright (GMaps, offline) · DomCrawler + JSON-LD"),
        ("AI", "Google Gemini API"),
        ("Peta & Tempat", "OpenStreetMap + Google Places API"),
        ("Autentikasi", "Laravel Sanctum (cookie httpOnly)"),
        ("Pengujian", "PHPUnit + ESLint"),
    ]
    y = 1.55
    for label, value in rows:
        rect(s, 0.4, y, 2.8, 0.5, NAVY)
        txt(s, 0.5, y, 2.6, 0.5, [{"t": label, "sz": 12, "b": True, "c": WHITE}], anchor="m")
        rect(s, 3.2, y, 5.5, 0.5, LIGHT, ACCENT)
        txt(s, 3.3, y, 5.3, 0.5, [{"t": value, "sz": 11, "c": TEXT}], anchor="m")
        y += 0.56

    rect(s, 9.0, 1.55, 4.0, 4.05, PALE, ACCENT)
    txt(s, 9.15, 1.65, 3.7, 0.4, [{"t": "Teori Dasar", "sz": 15, "b": True, "c": NAVY}])
    teori = [
        "RSS Feed — format XML distribusi konten",
        "JSON-LD — metadata terstruktur (schema.org)",
        "Web Scraping — ekstraksi data halaman web",
        "REST API — komunikasi backend ↔ frontend",
        "Client-Server — frontend & backend terpisah",
        "MVC Pattern — pemisahan logika & tampilan",
        "Haversine — jarak antar koordinat GPS",
        "JSON — format pertukaran data API",
    ]
    txt(
        s,
        9.15,
        2.1,
        3.75,
        3.4,
        [{"t": "•  " + t, "sz": 11, "c": TEXT, "sa": 8, "ls": 1.05} for t in teori],
    )
    notes(s, "Playwright hanya dipakai offline untuk mengambil data tempat; website tetap memakai PHP.")


# --------------------------------------------------------------------- slide 6
def slide_metode(prs):
    s = new_slide(prs)
    chrome(s, "Metode Pengembangan — Iteratif & Inkremental", "Dibangun bertahap, tiap tahap diuji, lalu diperbaiki", 6)

    # kiri atas: alasan memilih
    rect(s, 0.4, 1.5, 6.1, 2.95, LIGHT, ACCENT)
    txt(s, 0.55, 1.58, 5.8, 0.4, [{"t": "Kenapa pakai metode ini?", "sz": 16, "b": True, "c": NAVY}])
    alasan = [
        ("🧩  ", "Pekerjaan besar dipecah kecil. ", "Sistem punya 3 modul dan 2 aplikasi; dibangun satu bagian dulu, lalu bagian berikutnya."),
        ("🔁  ", "Tiap bagian langsung diuji. ", "Kesalahan ketahuan lebih awal dan tidak menumpuk sampai akhir."),
        ("💬  ", "Mudah menyesuaikan. ", "Kalau kebutuhan berubah, perbaikan cukup di iterasi berikutnya."),
        ("👤  ", "Cocok dikerjakan sendiri. ", "Tidak butuh tim besar atau rapat rutin."),
    ]
    paras = []
    for emo, bold, rest in alasan:
        paras.append(
            {
                "runs": [
                    (emo, {"sz": 10.5}),
                    (bold, {"sz": 10.5, "b": True, "c": NAVY}),
                    (rest, {"sz": 10.5, "c": TEXT}),
                ],
                "sa": 5,
                "ls": 1.05,
            }
        )
    txt(s, 0.55, 1.98, 5.8, 2.4, paras)

    # kiri bawah: kenapa bukan metode lain
    rect(s, 0.4, 4.6, 6.1, 2.4, WHITE, ACCENT)
    txt(s, 0.55, 4.68, 5.8, 0.4, [{"t": "Kenapa bukan metode lain?", "sz": 16, "b": True, "c": NAVY}])
    lain = [
        ("Waterfall", "desain dikunci di awal, sulit diubah saat ada temuan."),
        ("Prototype", "cocok untuk coba tampilan, kurang untuk sistem dua aplikasi."),
        ("Scrum", "butuh tim dan rapat rutin; tidak cocok kerja mandiri."),
        ("XP", "butuh dua programmer untuk pair programming."),
    ]
    paras = []
    for nama, rest in lain:
        paras.append(
            {
                "runs": [
                    ("•  ", {"c": ACCENT, "b": True, "sz": 10.5}),
                    (nama, {"b": True, "c": NAVY, "sz": 10.5}),
                    (" — " + rest, {"c": TEXT, "sz": 10.5}),
                ],
                "sa": 5,
                "ls": 1.05,
            }
        )
    txt(s, 0.55, 5.12, 5.8, 1.8, paras)

    # kanan atas: siklus
    rect(s, 6.75, 1.5, 6.15, 1.75, PALE, ACCENT)
    txt(s, 6.9, 1.57, 5.85, 0.35, [{"t": "Siklus tiap iterasi", "sz": 14, "b": True, "c": NAVY}])
    steps = [("Analisis", ""), ("Desain", ""), ("Buat", ""), ("Uji", ""), ("Perbaiki", "")]
    bw, gap = 1.02, 0.14
    total = len(steps) * bw + (len(steps) - 1) * gap
    x = 6.75 + (6.15 - total) / 2
    for i, (main, _) in enumerate(steps):
        rect(s, x, 2.0, bw, 0.62, NAVY)
        txt(s, x, 2.0, bw, 0.62, [{"t": main, "sz": 8.5, "b": True, "c": WHITE, "align": "c"}], anchor="m")
        if i < len(steps) - 1:
            txt(
                s, x + bw, 2.0, gap, 0.62,
                [{"t": "→", "sz": 13, "c": ACCENT, "align": "c"}],
                anchor="m", ml=0, mr=0,
            )
        x += bw + gap
    txt(
        s, 6.9, 2.72, 5.85, 0.4,
        [{"t": "↩  Diulang untuk setiap bagian (inkrement)", "sz": 10, "c": MUTED}],
    )

    # kanan bawah: intinya
    rect(s, 6.75, 3.4, 6.15, 3.6, LIGHT, ACCENT)
    txt(s, 6.9, 3.48, 5.85, 0.4, [{"t": "Intinya", "sz": 16, "b": True, "c": NAVY}])
    txt(
        s,
        6.9,
        3.95,
        5.85,
        2.9,
        [
            {
                "runs": [
                    ("Iteratif", {"b": True, "c": NAVY, "sz": 11.5}),
                    (" = dikerjakan berulang. Setiap putaran: rencana → buat → uji → perbaiki.", {"c": TEXT, "sz": 11.5}),
                ],
                "sa": 8,
                "ls": 1.08,
            },
            {
                "runs": [
                    ("Inkremental", {"b": True, "c": NAVY, "sz": 11.5}),
                    (" = hasilnya bertambah. Tiap putaran menghasilkan bagian yang sudah jalan, bukan cuma rancangan.", {"c": TEXT, "sz": 11.5}),
                ],
                "sa": 8,
                "ls": 1.08,
            },
            {
                "runs": [
                    ("Hasil akhir: ", {"b": True, "c": NAVY, "sz": 11.5}),
                    ("4 inkrement yang dibahas di slide berikutnya.", {"c": TEXT, "sz": 11.5}),
                ],
                "ls": 1.08,
            },
        ],
    )
    notes(
        s,
        "Jawaban kalau ditanya: 'Kenapa tidak waterfall?' — karena desain sistem dua aplikasi "
        "sulit dikunci di awal; iteratif memberi ruang perbaikan tanpa membongkar semuanya.",
    )


# --------------------------------------------------------------------- slide 7
def slide_gambaran(prs):
    s = new_slide(prs)
    chrome(s, "Gambaran Besar Pengerjaan", "Empat inkrement — dari fondasi sampai penyempurnaan", 7)

    inkremen = [
        (
            "Inkrement 1",
            "Fondasi & Berita",
            "✅ Selesai",
            GREEN_TX,
            [
                "ERD, migrasi, REST API",
                "Frontend dasar",
                "RSS + filter wilayah",
                "Ringkasan AI",
            ],
        ),
        (
            "Inkrement 2",
            "Direktori Tempat",
            "✅ Selesai",
            GREEN_TX,
            [
                "Scraping Google Maps",
                "Import + deteksi kecamatan",
                "Pengayaan OSM/Places/Wikipedia",
                "Peta & tempat terdekat",
            ],
        ),
        (
            "Inkrement 3",
            "Lowongan",
            "✅ Selesai",
            GREEN_TX,
            [
                "Scraper JSON-LD Kitalulus & Dealls",
                "Dedup + saring Tangsel",
                "Auto-tayang",
                "Kedaluwarsa 30 hari",
            ],
        ),
        (
            "Inkrement 4",
            "Penyempurnaan",
            "🔄 Berjalan",
            BLUE2,
            [
                "Ulasan & rating",
                "Dasbor admin",
                "Pencarian & filter",
                "Pengujian + deployment",
            ],
        ),
    ]

    x = 0.44
    for label, title, status, scolor, items in inkremen:
        rect(s, x, 1.55, 2.95, 3.55, LIGHT, ACCENT)
        rect(s, x, 1.55, 2.95, 0.5, NAVY)
        txt(s, x + 0.08, 1.55, 2.8, 0.5, [{"t": label, "sz": 11, "b": True, "c": WHITE}], anchor="m")
        txt(s, x + 0.12, 2.12, 2.7, 0.55, [{"t": title, "sz": 14, "b": True, "c": NAVY, "ls": 0.95}])
        txt(s, x + 0.12, 2.68, 2.7, 0.3, [{"t": status, "sz": 10, "b": True, "c": scolor}])
        txt(
            s,
            x + 0.12,
            3.02,
            2.72,
            1.95,
            [{"t": "•  " + it, "sz": 9.5, "c": TEXT, "sa": 5, "ls": 1.02} for it in items],
        )
        x += 3.17

    rect(s, 0.4, 5.5, 12.53, 1.3, PALE, ACCENT)
    txt(
        s,
        0.6,
        5.58,
        12.2,
        1.15,
        [
            {
                "runs": [
                    ("Arsitektur:  ", {"b": True, "c": NAVY, "sz": 11.5}),
                    ("Next.js 16 → REST API Laravel 13 → MySQL 8", {"c": TEXT, "sz": 11.5}),
                ],
                "sa": 5,
            },
            {
                "runs": [
                    ("Penjadwalan otomatis:  ", {"b": True, "c": NAVY, "sz": 11.5}),
                    ("berita tiap jam · lowongan tiap 3 jam · kedaluwarsa harian", {"c": TEXT, "sz": 11.5}),
                ],
            },
        ],
    )
    notes(
        s,
        "Empat inkrement ini yang menjadi peta progres. Tiga sudah selesai, satu sedang berjalan. "
        "Urutannya sengaja: fondasi dulu, baru fitur yang bergantung padanya.",
    )


# --------------------------------------------------------------------- slide 8
def slide_data(prs):
    s = new_slide(prs)
    chrome(s, "Analisis Kebutuhan Data & Sumbernya", "Data wajib tiap fitur dan portal asalnya", 8)

    widths = [2.0, 4.1, 6.3]
    rows = [
        ["Fitur", "Data Wajib", "Portal / Sumber Data"],
        [
            "🗞️  Berita",
            "judul, URL sumber, ringkasan, tanggal publikasi",
            "Google News · Bing News · ANTARA Banten · TangerangNews · BantenNews · PosBanten · SinarBanten",
        ],
        [
            "💼  Lowongan",
            "judul, perusahaan, jenis, URL asli, sumber, deskripsi",
            "Kitalulus · Dealls (JSON-LD JobPosting)",
        ],
        [
            "🗺️  Direktori",
            "nama, alamat, koordinat (lat/lng), kecamatan, jenis",
            "Google Maps (scraping) · OpenStreetMap (Overpass) · Google Places API · Wikipedia",
        ],
        [
            "⭐  Ulasan",
            "rating 1–5, pengguna yang login",
            "Input warga melalui form aplikasi",
        ],
    ]
    simple_table(
        s, 0.45, 1.55, widths, rows,
        header_h=0.45, row_h=1.02, body_sz=10.5, head_sz=11,
        aligns=["l", "l", "l"], first_col_bold=True,
    )

    txt(
        s,
        0.45,
        6.28,
        12.45,
        0.75,
        [
            {
                "t": "Data opsional — berita: thumbnail, kategori, kecamatan · lowongan: gaji, kualifikasi, kecamatan · direktori: rating, foto, telepon, website · ulasan: komentar.",
                "sz": 9.5,
                "c": MUTED,
                "ls": 1.02,
            },
            {
                "t": "💡  Koordinat lat/lng adalah kunci fitur 'tempat terdekat' dan deteksi kecamatan otomatis.",
                "sz": 10,
                "b": True,
                "c": NAVY,
                "sb": 3,
            },
        ],
    )
    notes(
        s,
        "Sebutkan bahwa portal berita yang dipakai adalah media lokal Banten plus agregator Google/Bing News. "
        "Lowongan hanya dari dua portal yang menyediakan JSON-LD.",
    )


# --------------------------------------------------------------------- slide 9
def slide_teknik(prs):
    s = new_slide(prs)
    chrome(s, "Teknik Pengumpulan Data", "Empat cara data masuk ke sistem", 9)

    cards = [
        (
            "📡  RSS Aggregation",
            "Untuk: Berita (Google News, Bing News, media lokal)",
            "Tools: Laravel HTTP Client + FeedParser",
            0.4, 1.55,
        ),
        (
            "🕷️  Structured Scraping",
            "Untuk: Lowongan (Kitalulus, Dealls) dan data tempat (offline)",
            "Tools: DomCrawler + JSON-LD · Playwright",
            6.75, 1.55,
        ),
        (
            "🔌  API Integration",
            "Untuk: OpenStreetMap, Google Places, Gemini AI",
            "Tools: HTTP + JSON (REST)",
            0.4, 4.15,
        ),
        (
            "👤  User Input",
            "Untuk: Ulasan & rating warga",
            "Tools: Form frontend → REST API → MySQL",
            6.75, 4.15,
        ),
    ]
    for title, untuk, tools, x, y in cards:
        rect(s, x, y, 6.18, 2.4, LIGHT, ACCENT)
        txt(s, x + 0.2, y + 0.12, 5.8, 0.5, [{"t": title, "sz": 17, "b": True, "c": NAVY}])
        txt(s, x + 0.2, y + 0.72, 5.8, 0.55, [{"t": untuk, "sz": 12, "c": TEXT, "ls": 1.05}])
        txt(s, x + 0.2, y + 1.4, 5.8, 0.45, [{"t": tools, "sz": 11, "b": True, "c": BLUE2}])
    notes(s, "RSS sekarang hanya untuk berita. Lowongan memakai scraping JSON-LD karena datanya terstruktur.")


# --------------------------------------------------------------------- slide 10
def slide_batasan(prs):
    s = new_slide(prs)
    chrome(s, "Batasan dalam Pengumpulan Data", "Kendala nyata di lapangan dan cara mengatasinya", 10)

    cards = [
        (
            "🚫  Tidak ada API gratis Google Maps",
            "Google Maps tidak menyediakan endpoint pencarian teks gratis. Solusi: browser automation Playwright (Python), dijalankan offline.",
            0.4, 1.55,
        ),
        (
            "©️  Hak cipta konten berita",
            "Isi artikel milik penerbit. Sistem hanya menyimpan ringkasan + tautan ke sumber asli, bukan konten penuh.",
            6.75, 1.55,
        ),
        (
            "🗺️  Cakupan wilayah terbatas",
            "Data hanya untuk 7 kecamatan Tangerang Selatan. Tempat di luar wilayah diabaikan.",
            0.4, 4.15,
        ),
        (
            "🔎  Sumber lowongan terbatas pada JSON-LD",
            "Hanya Kitalulus dan Dealls yang menyediakan data terstruktur. Dari 184 baris hasil RSS, hanya 9 yang layak tayang (≈5%); ±82% tautannya redirect agregator dan ±60% sudah basi.",
            6.75, 4.15,
        ),
    ]
    for title, body, x, y in cards:
        rect(s, x, y, 6.18, 2.4, LIGHT, ACCENT)
        txt(s, x + 0.2, y + 0.12, 5.8, 0.45, [{"t": title, "sz": 14.5, "b": True, "c": NAVY}])
        txt(s, x + 0.2, y + 0.62, 5.8, 1.65, [{"t": body, "sz": 11, "c": TEXT, "ls": 1.08}])
    notes(
        s,
        "Ini bagian pembeda: keputusan meninggalkan RSS lowongan didasarkan pada data, bukan asumsi. "
        "Sebut angka 9 dari 184 baris (sekitar 5 persen).",
    )


# --------------------------------------------------------------------- slide 11
def slide_alur_berita(prs):
    s = new_slide(prs)
    chrome(s, "Alur Pemrosesan Data — Berita", "Dari feed RSS sampai tampil di halaman /berita", 11)

    steps = [
        ("RSS Feed", "Google News · Bing\nMedia lokal"),
        ("FeedParser", "Urai XML"),
        ("Filter Wilayah", "Skor bukti\nTangsel"),
        ("Ambil Artikel", "Buka URL asli\ncek robots.txt"),
        ("AI Summarize", "Gemini\n(fallback: feed)"),
        ("Categorizer", "Kategori &\nkecamatan"),
        ("Simpan DB", "published / draft\n/ arsip"),
        ("Frontend", "/berita\nbadge AI"),
    ]
    flow(s, steps, 1.75, 1.36, 1.7, 0.17)

    rect(s, 0.4, 4.0, 12.53, 2.6, PALE, ACCENT)
    txt(
        s,
        0.6,
        4.12,
        12.2,
        2.35,
        [
            {"t": "⚠️  Fallback: jika Gemini tidak tersedia → pakai ringkasan dari RSS (tanpa badge AI).", "sz": 11.5, "c": TEXT, "sa": 8, "ls": 1.05},
            {"t": "⚠️  Filter wilayah: skor bukti Tangsel (+3/+2/+1). Bukti kuat → publish; ambigu → draft; penanda wilayah lain → tolak.", "sz": 11.5, "c": TEXT, "sa": 8, "ls": 1.05},
            {"t": "⏰  Batas umur 30 hari — berita yang lebih lama diarsipkan otomatis.", "sz": 11.5, "c": TEXT, "ls": 1.05},
        ],
    )
    notes(
        s,
        "Dua hal yang sering ditanya: kenapa ada draft dan arsip, dan kenapa AI tidak selalu dipakai. "
        "Jawab: filter wilayah bertingkat, dan AI punya fallback.",
    )


# --------------------------------------------------------------------- slide 12
def slide_alur_lowongan(prs):
    s = new_slide(prs)
    chrome(s, "Alur Pemrosesan Data — Lowongan", "Dari portal lowongan sampai tayang otomatis", 12)

    steps = [
        ("Sumber", "Kitalulus ·\nDealls"),
        ("Halaman", "Lokasi /\nsitemap"),
        ("Detail", "Buka\nlowongan"),
        ("JSON-LD", "JobPosting"),
        ("Saring", "Tangsel +\n≤ 30 hari"),
        ("Dedup", "URL + judul"),
        ("Klasifikasi", "Jenis, kategori,\nkecamatan"),
        ("Simpan", "Auto-tayang\n(deskripsi + URL)"),
        ("Frontend", "/lowongan"),
    ]
    flow(s, steps, 1.75, 1.18, 1.85, 0.18, main_sz=9, sub_sz=7.2)

    rect(s, 0.4, 4.1, 12.53, 2.55, PALE, ACCENT)
    txt(
        s,
        0.6,
        4.2,
        12.2,
        2.35,
        [
            {"t": "📌  Kitalulus & Dealls: JSON-LD menyimpan deskripsi asli + URL asli; lowongan relevan langsung tayang tanpa kurasi manual.", "sz": 11, "c": TEXT, "sa": 8, "ls": 1.05},
            {"t": "🔁  Kurasi manual tetap tersedia sebagai cadangan (lowongan:publish) bila ada data yang perlu diperiksa.", "sz": 11, "c": TEXT, "sa": 8, "ls": 1.05},
            {"t": "⏰  lowongan:expire terjadwal → lowongan lewat 30 hari otomatis kedaluwarsa.", "sz": 11, "c": TEXT, "ls": 1.05},
        ],
    )
    notes(
        s,
        "Alur ini yang berubah paling besar. Dulu ada langkah 'kurasi admin' wajib; sekarang otomatis, "
        "karena data JSON-LD sudah terstruktur dan tautannya langsung ke sumber.",
    )


# --------------------------------------------------------------------- slide 13
def slide_direktori(prs):
    s = new_slide(prs)
    chrome(s, "Alur Pengumpulan Data — Direktori Tempat", "Empat lapisan: kumpulkan, impor, perkaya, sajikan", 13)

    layers = [
        ("Layer 1", "Pengumpulan", ["File query (.txt)", "gmaps_scraper.py", "Hasil JSON"]),
        ("Layer 2", "Import", ["GmapsImportCommand", "Validasi & dedup", "Deteksi kecamatan", "Simpan ke DB"]),
        ("Layer 3", "Pengayaan", ["OpenStreetMap API", "Google Places ID", "Wikipedia", "Gemini AI"]),
        ("Layer 4", "Sajian", ["REST API Laravel", "Frontend Next.js", "Data live & terdekat", "Ulasan warga"]),
    ]
    x = 0.42
    for i, (label, title, items) in enumerate(layers):
        rect(s, x, 1.6, 2.95, 3.7, LIGHT, ACCENT)
        rect(s, x, 1.6, 2.95, 0.62, NAVY)
        txt(s, x + 0.1, 1.6, 2.75, 0.62,
            [{"t": label, "sz": 13, "b": True, "c": WHITE},
             {"t": title, "sz": 9.5, "c": ACCENT}],
            anchor="m")
        txt(s, x + 0.12, 2.4, 2.72, 2.75,
            [{"t": "•  " + it, "sz": 10.5, "c": TEXT, "sa": 6, "ls": 1.05} for it in items])
        if i < 3:
            txt(s, x + 2.95, 3.1, 0.22, 0.6,
                [{"t": "→", "sz": 16, "b": True, "c": ACCENT, "align": "c"}], anchor="m", ml=0, mr=0)
        x += 3.17

    rect(s, 0.4, 5.55, 12.53, 1.2, PALE, ACCENT)
    txt(
        s,
        0.6,
        5.65,
        12.2,
        1.0,
        [
            {"t": "💡  Scraping (Layer 1) dipisah dari import (Layer 2): kalau scraping gagal di tengah, data JSON tetap aman.", "sz": 11.5, "c": TEXT, "sa": 6, "ls": 1.05},
            {"t": "🔁  Pengayaan berlapis membuat sistem tetap jalan walau API berbayar tidak tersedia.", "sz": 11.5, "c": TEXT, "ls": 1.05},
        ],
    )
    notes(s, "Tekankan pemisahan scraping dan import: kegagalan satu tahap tidak menghapus data yang sudah ada.")


# --------------------------------------------------------------------- slide 14
def slide_progress(prs):
    s = new_slide(prs)
    chrome(
        s,
        "Progress Pengerjaan Saat Ini",
        "Semua modul utama berjalan; 210 test / 718 assertion lulus",
        14,
    )

    rows = [
        ["Komponen", "Status"],
        ["Database & migrasi semua tabel", "✅ Selesai"],
        ["Backend REST API semua endpoint", "✅ Selesai"],
        ["Autentikasi (login/register/logout)", "✅ Selesai"],
        ["Dashboard admin: moderasi & kurasi cadangan", "✅ Selesai"],
        ["Frontend semua halaman utama", "✅ Selesai"],
        ["Scraping GMaps + lowongan JSON-LD", "✅ Selesai"],
        ["Auto-tayang lowongan + scheduler 3 jam", "✅ Aktif"],
        ["Import data direktori ke database", "✅ Selesai"],
        ["AI summarizer berita via Gemini", "✅ Selesai"],
        ["Unit & Feature Testing (210 test · 718 assertion)", "✅ Lulus"],
    ]
    simple_table(
        s, 0.45, 1.5, [8.6, 3.95], rows,
        header_h=0.42, row_h=0.47, body_sz=10.5, head_sz=11,
        aligns=["l", "c"], first_col_bold=False,
    )
    notes(
        s,
        "Kalau ditanya kenapa angka tesnya banyak: itu termasuk pengujian alur berita, direktori, lowongan, "
        "autentikasi, dan perintah artisan.",
    )


# --------------------------------------------------------------------- slide 15
def slide_planning(prs):
    s = new_slide(prs)
    chrome(s, "Rencana Pengerjaan Selanjutnya", "Target: sidang akhir Maret 2027", 15)

    plans = [
        ("Nov 2026", "Polishing UI, halaman ulasan lengkap, pencarian global"),
        ("Des 2026", "Responsif mobile, optimasi performa, perbaikan data direktori"),
        ("Jan 2027", "User testing, perbaikan bug, monitoring kualitas sumber scraping"),
        ("Feb 2027", "Bab 4 (Implementasi) + Bab 5 (Pengujian & UAT)"),
        ("Mar 2027", "Bab 6 (Kesimpulan), deploy ke hosting, sidang akhir"),
    ]
    x = 0.42
    for bulan, isi in plans:
        rect(s, x, 1.6, 2.36, 2.95, LIGHT, ACCENT)
        rect(s, x, 1.6, 2.36, 0.55, NAVY)
        txt(s, x + 0.08, 1.6, 2.2, 0.55, [{"t": bulan, "sz": 13, "b": True, "c": WHITE}], anchor="m")
        txt(s, x + 0.12, 2.3, 2.15, 2.1, [{"t": isi, "sz": 10.5, "c": TEXT, "ls": 1.1}])
        x += 2.54

    rect(s, 0.4, 4.9, 12.53, 1.05, PALE, ACCENT)
    txt(
        s,
        0.6,
        4.9,
        12.2,
        1.05,
        [{"t": "Terima Kasih — Silakan Bertanya 🙏", "sz": 24, "b": True, "c": NAVY, "align": "c"}],
        anchor="m",
    )
    txt(
        s,
        0.4,
        6.15,
        12.53,
        0.5,
        [{"t": "TANGSEL LIA — Platform Informasi Lokal Tangerang Selatan", "sz": 12, "c": MUTED, "align": "c"}],
    )
    notes(s, "Tutup dengan mengulang tiga pilar, lalu buka sesi tanya jawab.")


# ------------------------------------------------------------------------- main
def build(path):
    prs = Presentation()
    prs.slide_width = Inches(W)
    prs.slide_height = Inches(H)

    slide_cover(prs)
    slide_latar(prs)
    slide_rumusan(prs)
    slide_lingkup(prs)
    slide_teknologi(prs)
    slide_metode(prs)
    slide_gambaran(prs)
    slide_data(prs)
    slide_teknik(prs)
    slide_batasan(prs)
    slide_alur_berita(prs)
    slide_alur_lowongan(prs)
    slide_direktori(prs)
    slide_progress(prs)
    slide_planning(prs)

    prs.save(path)
    print("tersimpan:", path, "| slide:", len(prs.slides))


if __name__ == "__main__":
    build("UTS_TANGSEL_LIA.pptx")
