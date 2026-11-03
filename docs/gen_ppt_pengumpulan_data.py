# -*- coding: utf-8 -*-
"""
Membuat deck presentasi:
Analisis Kebutuhan Data, Batasan Pengumpulan Data, Teknik Pengumpulan Data.

Jalankan:  python docs/gen_ppt_pengumpulan_data.py
Keluaran:  docs/Presentation - Pengumpulan Data - TANGSEL LIA.pptx
"""

from pathlib import Path

from pptx import Presentation
from pptx.dml.color import RGBColor
from pptx.enum.shapes import MSO_SHAPE
from pptx.enum.text import MSO_ANCHOR, PP_ALIGN
from pptx.util import Inches, Pt

# --------------------------------------------------------------------------
# Konstanta desain
# --------------------------------------------------------------------------

W, H = 13.333, 7.5
M = 0.75                 # margin kiri/kanan
CW = W - 2 * M           # lebar konten (11.833)

FONT = "Segoe UI"
MONO = "Consolas"

NAVY = "0F172A"
SLATE800 = "1E293B"
SLATE700 = "334155"
SLATE600 = "475569"
SLATE500 = "64748B"
SLATE400 = "94A3B8"
SLATE300 = "CBD5E1"
SLATE200 = "E2E8F0"
SLATE100 = "F1F5F9"
SLATE50 = "F8FAFC"
WHITE = "FFFFFF"

BLUE = "2563EB"
BLUE600 = "2563EB"
BLUE500 = "3B82F6"
BLUE300 = "38BDF8"
BLUE50 = "EFF6FF"

AMBER = "B45309"
AMBER50 = "FFFBEB"
EMERALD = "047857"
EMERALD50 = "ECFDF5"
ROSE = "BE123C"
ROSE50 = "FFF1F2"

REPO = Path(__file__).resolve().parents[1]
OUT = REPO / "docs" / "Presentation - Pengumpulan Data - TANGSEL LIA.pptx"


def rgb(hexstr: str) -> RGBColor:
    return RGBColor.from_string(hexstr)


def r(text, size=11, bold=False, color=NAVY, font=FONT, italic=False):
    return (text, {"size": size, "bold": bold, "color": color, "font": font, "italic": italic})


def p(runs, align=PP_ALIGN.LEFT, after=0, before=0, line=1.0):
    if isinstance(runs, tuple):
        runs = [runs]
    return {"runs": runs, "align": align, "after": after, "before": before, "line": line}


def new_slide(prs, dark=False):
    s = prs.slides.add_slide(prs.slide_layouts[6])
    fill = s.background.fill
    fill.solid()
    fill.fore_color.rgb = rgb(NAVY if dark else WHITE)
    return s


def add_text(slide, x, y, w, h, paras, anchor=MSO_ANCHOR.TOP):
    box = slide.shapes.add_textbox(Inches(x), Inches(y), Inches(w), Inches(h))
    tf = box.text_frame
    tf.word_wrap = True
    tf.margin_left = 0
    tf.margin_right = 0
    tf.margin_top = 0
    tf.margin_bottom = 0
    tf.vertical_anchor = anchor
    for i, spec in enumerate(paras):
        par = tf.paragraphs[0] if i == 0 else tf.add_paragraph()
        par.alignment = spec["align"]
        par.space_after = Pt(spec["after"])
        par.space_before = Pt(spec["before"])
        par.line_spacing = spec["line"]
        for text, st in spec["runs"]:
            run = par.add_run()
            run.text = text
            f = run.font
            f.name = st["font"]
            f.size = Pt(st["size"])
            f.bold = st["bold"]
            f.italic = st["italic"]
            f.color.rgb = rgb(st["color"])
    return box


def add_shape(slide, kind, x, y, w, h, fill=None, line=None, line_w=0.75, radius=None):
    sh = slide.shapes.add_shape(kind, Inches(x), Inches(y), Inches(w), Inches(h))
    if radius is not None:
        try:
            sh.adjustments[0] = radius
        except Exception:
            pass
    if fill is None:
        sh.fill.background()
    else:
        sh.fill.solid()
        sh.fill.fore_color.rgb = rgb(fill)
    if line is None:
        sh.line.fill.background()
    else:
        sh.line.color.rgb = rgb(line)
        sh.line.width = Pt(line_w)
    sh.shadow.inherit = False
    sh.text_frame.word_wrap = True
    return sh


def shape_text(sh, paras, anchor=MSO_ANCHOR.MIDDLE, pad=0.18):
    tf = sh.text_frame
    tf.word_wrap = True
    tf.margin_left = Inches(pad)
    tf.margin_right = Inches(pad)
    tf.margin_top = Inches(pad * 0.8)
    tf.margin_bottom = Inches(pad * 0.8)
    tf.vertical_anchor = anchor
    for i, spec in enumerate(paras):
        par = tf.paragraphs[0] if i == 0 else tf.add_paragraph()
        par.alignment = spec["align"]
        par.space_after = Pt(spec["after"])
        par.space_before = Pt(spec["before"])
        par.line_spacing = spec["line"]
        for text, st in spec["runs"]:
            run = par.add_run()
            run.text = text
            f = run.font
            f.name = st["font"]
            f.size = Pt(st["size"])
            f.bold = st["bold"]
            f.italic = st["italic"]
            f.color.rgb = rgb(st["color"])


def card(slide, x, y, w, h, fill=SLATE50, line=None, radius=0.08, pad=0.18,
         paras=None, anchor=MSO_ANCHOR.MIDDLE):
    sh = add_shape(slide, MSO_SHAPE.ROUNDED_RECTANGLE, x, y, w, h,
                   fill=fill, line=line, radius=radius)
    if paras:
        shape_text(sh, paras, anchor=anchor, pad=pad)
    return sh


def pill(slide, x, y, w, h, text, fill, color, size=9, bold=True):
    sh = add_shape(slide, MSO_SHAPE.ROUNDED_RECTANGLE, x, y, w, h, fill=fill, radius=0.5)
    shape_text(sh, [p(r(text, size=size, bold=bold, color=color), align=PP_ALIGN.CENTER)],
               anchor=MSO_ANCHOR.MIDDLE, pad=0.05)
    return sh


def arrow(slide, x, y, w=0.3, h=0.22, color=SLATE300):
    return add_shape(slide, MSO_SHAPE.RIGHT_ARROW, x, y, w, h, fill=color)


def header(slide, eyebrow, title, lead=None, dark=False):
    title_color = WHITE if dark else NAVY
    lead_color = SLATE300 if dark else SLATE500
    add_text(slide, M, 0.40, CW, 0.26,
             [p(r(eyebrow.upper(), size=9.5, bold=True, color=BLUE300 if dark else BLUE))])
    add_text(slide, M, 0.64, CW, 0.62,
             [p(r(title, size=25, bold=True, color=title_color))])
    add_shape(slide, MSO_SHAPE.RECTANGLE, M, 1.36, 0.55, 0.05, fill=BLUE if not dark else BLUE300)
    if lead:
        add_text(slide, M, 1.49, CW - 0.3, 0.3,
                 [p(r(lead, size=11, color=lead_color))])


def footer(slide, num, dark=False):
    line_color = SLATE800 if dark else SLATE200
    text_color = SLATE500 if dark else SLATE400
    add_shape(slide, MSO_SHAPE.RECTANGLE, M, 7.04, CW, 0.012, fill=line_color)
    add_text(slide, M, 7.13, 8.0, 0.24,
             [p(r("TANGSEL LIA  ·  Pengumpulan Data", size=8, color=text_color))])
    add_text(slide, W - M - 1.0, 7.13, 1.0, 0.24,
             [p(r(f"{num:02d}", size=8, bold=True, color=text_color), align=PP_ALIGN.RIGHT)])


def notes(slide, text):
    slide.notes_slide.notes_text_frame.text = text


def stat(slide, x, y, w, h, value, label, sub=None, accent=BLUE, fill=SLATE50):
    card(slide, x, y, w, h, fill=fill, anchor=MSO_ANCHOR.MIDDLE, pad=0.22, paras=[
        p(r(value, size=30, bold=True, color=accent), after=2),
        p(r(label, size=10.5, bold=True, color=SLATE800), after=2, line=1.05),
        p(r(sub, size=8.5, color=SLATE500), line=1.1) if sub else p(r("", size=8.5)),
    ])


def node(slide, x, y, w, h, num, title, sub, mono=False, accent=BLUE):
    card(slide, x, y, w, h, fill=SLATE50, pad=0.14, anchor=MSO_ANCHOR.MIDDLE, paras=[
        p([r(f"{num}  ", size=10, bold=True, color=accent),
           r(title, size=10.5, bold=True, color=NAVY, font=MONO if mono else FONT)], after=3, line=1.02),
        p(r(sub, size=8.5, color=SLATE500), line=1.12),
    ])


def flow_row(slide, y, items, h=1.25, gap=0.28, start=M):
    n = len(items)
    w = (CW - gap * (n - 1)) / n
    for i, item in enumerate(items):
        x = start + i * (w + gap)
        node(slide, x, y, w, h, *item)
        if i < n - 1:
            arrow(slide, x + w + (gap - 0.26) / 2, y + h / 2 - 0.11, w=0.26, h=0.22)


# --------------------------------------------------------------------------
# Isi presentasi
# --------------------------------------------------------------------------

prs = Presentation()
prs.slide_width = Inches(W)
prs.slide_height = Inches(H)

prs.core_properties.title = "Pengumpulan Data - TANGSEL LIA"
prs.core_properties.author = "Diko Daen Afinto"

# ---------------------------------------------------------------- 01 sampul
s = new_slide(prs, dark=True)
add_shape(s, MSO_SHAPE.RECTANGLE, 0, 0, 0.16, H, fill=BLUE)
add_text(s, 0.95, 0.95, 7.6, 0.3,
         [p(r("TUGAS AKHIR  ·  TEKNIK INFORMATIKA", size=10.5, bold=True, color=BLUE300))])
add_text(s, 0.95, 1.35, 7.8, 1.9, [
    p(r("Pengumpulan Data", size=42, bold=True, color=WHITE), line=1.02),
    p(r("pada TANGSEL LIA", size=42, bold=True, color=WHITE), line=1.02),
])
add_text(s, 0.95, 3.45, 7.3, 1.0,
         [p(r("Analisis kebutuhan data, batasan pengumpulan, dan teknik pengumpulan data "
              "untuk modul Berita, Lowongan, dan Tempat.", size=13.5, color=SLATE300), line=1.3)])
add_shape(s, MSO_SHAPE.RECTANGLE, 0.95, 4.55, 1.4, 0.05, fill=BLUE300)
add_text(s, 0.95, 4.85, 7.0, 0.8, [
    p(r("Platform Informasi Lokal Tangerang Selatan", size=11.5, bold=True, color=WHITE), after=3),
    p(r("Institut Teknologi Indonesia  ·  2026", size=10, color=SLATE400)),
])

items = [("01", "Kebutuhan Data", "Data apa yang wajib ada"),
         ("02", "Batasan", "Sampai mana boleh diambil"),
         ("03", "Teknik", "Komponen dan alurnya")]
y = 1.75
for num, title, sub in items:
    card(s, 9.25, y, 3.25, 1.08, fill="16233C", pad=0.22, anchor=MSO_ANCHOR.MIDDLE, paras=[
        p([r(num + "   ", size=11, bold=True, color=BLUE300),
           r(title, size=12.5, bold=True, color=WHITE)], after=2),
        p(r(sub, size=8.5, color=SLATE400)),
    ])
    y += 1.28

add_text(s, 9.25, 5.75, 3.25, 0.6,
         [p(r("24 sumber · 108 berita · 121 lowongan", size=8.5, color=SLATE500), line=1.3)])
notes(s, "Pembuka: penelitian ini bukan sekadar membangun tampilan, tetapi membangun sistem yang "
         "mengumpulkan data dari sumber nyata. Bagian ini menjelaskan data apa yang dibutuhkan, "
         "sampai mana data boleh diambil, dan komponen apa yang mengambilnya.")

# ---------------------------------------------------------------- 02 agenda
s = new_slide(prs)
header(s, "Agenda", "Tiga Pertanyaan yang Harus Bisa Dijawab")
add_text(s, M, 1.62, CW - 1.0, 0.32,
         [p(r("Semua pertanyaan penguji soal data akan kembali ke tiga hal ini.", size=11, color=SLATE500))])

cards = [
    ("01", "Analisis Kebutuhan Data",
     "Data apa yang wajib ada, untuk fitur apa, dan berapa banyak?", BLUE),
    ("02", "Batasan Pengumpulan Data",
     "Sampai mana data boleh dan mampu diambil, dan apa penanganannya?", AMBER),
    ("03", "Teknik Pengumpulan Data",
     "Komponen apa yang mengambilnya, dan bagaimana alur tiap fiturnya?", EMERALD),
]
x = M
for num, title, desc, accent in cards:
    card(s, x, 2.15, 3.72, 3.15, fill=SLATE50, anchor=MSO_ANCHOR.TOP, pad=0.3, paras=[
        p(r(num, size=34, bold=True, color=accent), after=6),
        p(r(title, size=14, bold=True, color=NAVY), after=6, line=1.1),
        p(r(desc, size=10, color=SLATE500), line=1.3),
    ])
    x += 3.72 + 0.335

card(s, M, 5.62, CW, 1.05, fill=NAVY, pad=0.3, anchor=MSO_ANCHOR.MIDDLE, paras=[
    p([r("Ujungnya satu: ", size=11, color=SLATE300),
       r("data yang bisa dipertanggungjawabkan", size=11, bold=True, color=WHITE),
       r(" — bukan sekadar data yang banyak.", size=11, color=SLATE300)]),
])
notes(s, "Tiga bagian ini satu alur: butuh apa, sampai mana boleh, ambil pakai apa. "
         "Jangan dijawab terpisah-pisah, karena penguji akan menanyakan kaitannya.")

# ---------------------------------------------------------------- 03 alur besar
s = new_slide(prs)
header(s, "Alur Besar", "Lima Tahap yang Selalu Berulang",
       "Berlaku untuk berita, lowongan, dan tempat — hanya komponennya yang berbeda.")

stages = [
    ("01", "Ambil", "Mendatangi sumber: feed, peta publik, situs resmi, atau berkas."),
    ("02", "Urai", "Data mentah diubah jadi bentuk seragam: judul, isi, tanggal, lokasi."),
    ("03", "Saring", "Buang duplikat, buang yang tidak relevan, potong yang kepanjangan."),
    ("04", "Simpan", "Masuk penyimpanan, lengkap dengan status dan asal-usul data."),
    ("05", "Sajikan", "Dibaca halaman publik lewat API."),
]
x = M
bw = (CW - 4 * 0.2) / 5
for num, title, desc in stages:
    card(s, x, 1.98, bw, 1.62, fill=SLATE50, anchor=MSO_ANCHOR.TOP, pad=0.2, paras=[
        p(r(num, size=10, bold=True, color=BLUE), after=4),
        p(r(title, size=14, bold=True, color=NAVY), after=5),
        p(r(desc, size=8.5, color=SLATE500), line=1.2),
    ])
    x += bw + 0.2

lanes = [
    ("BERITA", "9 feed RSS", "NewsFetcher", "FeedParser", "ringkas + kategori", "berita", "108"),
    ("LOWONGAN", "15 feed RSS", "LowonganFetcher", "ekstrak field", "saring wilayah", "kurasi admin", "121"),
    ("TEMPAT", "peta publik", "OsmImporter", "rapikan atribut", "kecamatan terdekat", "3 tabel direktori", "1.589"),
]
y = 3.85
for name, a, b, c, d, e, n in lanes:
    card(s, M, y, CW, 0.72, fill=WHITE, line=SLATE200, pad=0.18, anchor=MSO_ANCHOR.MIDDLE, paras=[
        p([r(f"{name}   ", size=9, bold=True, color=BLUE),
           r(f"{a}  →  {b}  →  {c}  →  {d}  →  {e}", size=9, color=SLATE600),
           r(f"   {n}", size=9, bold=True, color=NAVY)], line=1.1),
    ])
    y += 0.86

card(s, M, 6.44, CW, 0.52, fill=AMBER50, pad=0.18, anchor=MSO_ANCHOR.MIDDLE, paras=[
    p([r("Bedanya cuma satu: ", size=9, bold=True, color=AMBER),
       r("lowongan berhenti dulu di kurasi admin, berita langsung tayang.", size=9, color=AMBER)]),
])
notes(s, "Kalau ditanya 'alurnya bagaimana', jawab lima tahap ini dulu, baru sebut komponennya. "
         "Lima tahap ini yang membuat jawaban tetap terstruktur walau pertanyaannya melompat.")

# ---------------------------------------------------------------- 04 pembatas 1
s = new_slide(prs, dark=True)
add_shape(s, MSO_SHAPE.RECTANGLE, 0, 0, 0.16, H, fill=BLUE)
add_text(s, M, 1.95, 2.4, 1.7, [p(r("01", size=88, bold=True, color="1D4ED8"))])
add_text(s, M, 3.45, 10.9, 0.9, [p(r("Analisis Kebutuhan Data", size=38, bold=True, color=WHITE))])
add_text(s, M, 4.45, 9.6, 0.7,
         [p(r("Data apa yang wajib ada sebelum sistem dibangun.", size=14, color=SLATE300))])
add_text(s, M, 5.55, 9.0, 0.4, [p(r("BAGIAN 1 DARI 3", size=9, bold=True, color=SLATE500))])
notes(s, "Masuk bagian pertama. Tekankan: ini tahap menentukan, bukan tahap mengambil.")

# ---------------------------------------------------------------- 05 prinsip
s = new_slide(prs)
header(s, "01 · Analisis Kebutuhan Data", "Urutan Berpikirnya",
       "Fitur tidak bisa dibangun sebelum jelas data apa yang harus ada.")

steps = [("1", "Fitur", "Apa yang harus bisa dilakukan pengguna"),
         ("2", "Kebutuhan Data", "Data apa yang harus tersedia untuk itu"),
         ("3", "Atribut Wajib", "Bagian mana yang tidak boleh kosong"),
         ("4", "Penyimpanan", "Bentuk tabel dan siapa pengisinya")]
x = M
sw = (CW - 3 * 0.36) / 4
for num, title, sub in steps:
    card(s, x, 2.02, sw, 1.22, fill=BLUE50, pad=0.2, anchor=MSO_ANCHOR.MIDDLE, paras=[
        p([r(num + "  ", size=11, bold=True, color=BLUE),
           r(title, size=11.5, bold=True, color=NAVY)], after=3),
        p(r(sub, size=8.5, color=SLATE500), line=1.15),
    ])
    if num != "4":
        arrow(s, x + sw + 0.06, 2.52, w=0.24, h=0.22, color=BLUE300)
    x += sw + 0.36

reasons = [
    ("Supaya tidak bongkar ulang", "Data yang terlewat biasanya baru ketahuan di tengah pembangunan."),
    ("Supaya bisa dibuktikan", "Tiap data punya alasan dan sumber yang jelas."),
    ("Supaya jelas pengisinya", "Ketahuan komponen mana yang bertanggung jawab mengisi."),
]
x = M
for title, desc in reasons:
    card(s, x, 3.6, 3.72, 1.45, fill=SLATE50, anchor=MSO_ANCHOR.TOP, pad=0.24, paras=[
        p(r(title, size=11.5, bold=True, color=NAVY), after=5),
        p(r(desc, size=9, color=SLATE500), line=1.25),
    ])
    x += 3.72 + 0.335

card(s, M, 5.3, CW, 1.35, fill=WHITE, line=BLUE, pad=0.3, anchor=MSO_ANCHOR.MIDDLE, paras=[
    p([r("Hasil tahap ini ", size=11.5, bold=True, color=NAVY),
       r("bukan kode", size=11.5, bold=True, color=BLUE),
       r(", melainkan daftar: fitur ini butuh apa saja.", size=11.5, color=SLATE600)]),
])
notes(s, "Kalau ditanya 'apa hasil analisis kebutuhan data', jawab: daftar kebutuhan data per fitur. "
         "Bukan tabel database, bukan kode.")

# ---------------------------------------------------------------- 06 berita
s = new_slide(prs)
header(s, "Kebutuhan Data · Berita", "Enam Data Wajib dan Alasannya",
       "Berita adalah modul paling sederhana, tapi dua datanya menentukan kualitas.")

items = [
    ("Judul", "Identitas berita, dipakai untuk membuat alamat halaman."),
    ("Ringkasan", "Isi singkat agar pembaca tidak perlu membuka semuanya."),
    ("Gambar", "Foto utama dari halaman asli, untuk kartu berita."),
    ("Tanggal terbit", "Memisahkan berita baru dari berita lama yang baru ditemukan."),
    ("Alamat artikel asli", "Mencegah duplikat dan mengarahkan pembaca ke sumbernya."),
    ("Media & wilayah", "Sumber tepercaya, sekaligus dasar filter per kecamatan."),
]
x, y = M, 2.0
for i, (title, desc) in enumerate(items):
    card(s, x, y, 3.75, 1.32, fill=SLATE50, anchor=MSO_ANCHOR.TOP, pad=0.22, paras=[
        p(r(title, size=11.5, bold=True, color=NAVY), after=4),
        p(r(desc, size=9, color=SLATE500), line=1.25),
    ])
    if i % 3 == 2:
        x = M
        y += 1.45
    else:
        x += 3.75 + 0.29

card(s, M, 4.95, 7.35, 1.05, fill=BLUE50, pad=0.24, anchor=MSO_ANCHOR.MIDDLE, paras=[
    p([r("Dua tanggal disimpan: ", size=9.5, bold=True, color=BLUE),
       r("kapan media menerbitkan, dan kapan sistem mengambil.", size=9.5, color=SLATE600)], line=1.2),
])
card(s, 8.35, 4.95, 4.23, 1.05, fill=SLATE50, pad=0.24, anchor=MSO_ANCHOR.MIDDLE, paras=[
    p([r("Terkumpul: ", size=9.5, bold=True, color=NAVY),
       r("108 berita dari 9 sumber.", size=9.5, color=SLATE600)], line=1.2),
])

card(s, M, 6.2, CW, 0.5, fill=WHITE, line=SLATE200, pad=0.2, anchor=MSO_ANCHOR.MIDDLE, paras=[
    p(r("Isi artikel lengkap tidak disimpan — hanya ringkasan dan tautan. Itu keputusan etis, dibahas di bagian 2.",
        size=9, color=SLATE500)),
])
notes(s, "Sebut alamat asli dan dua tanggal sebagai dua data yang paling sering ditanya. "
         "Alamat asli: untuk dedup dan atribusi. Dua tanggal: supaya berita lama tidak tampil sebagai baru.")

# ---------------------------------------------------------------- 07 lowongan
s = new_slide(prs)
header(s, "Kebutuhan Data · Lowongan", "Delapan Data, Empat Status",
       "Lowongan meniru berita, tapi punya tambahan yang tidak dimiliki berita.")

chips = ["Posisi", "Perusahaan", "Jenis", "Deskripsi", "Syarat", "Cara melamar", "Alamat asli", "Batas waktu"]
x, y = M, 1.95
cw_, gap = 2.75, 0.28
for i, text in enumerate(chips):
    fill = BLUE50 if text != "Batas waktu" else "FEF3C7"
    color = BLUE if text != "Batas waktu" else AMBER
    card(s, x, y, cw_, 0.62, fill=fill, pad=0.16, anchor=MSO_ANCHOR.MIDDLE, paras=[
        p(r(text, size=10, bold=True, color=color), align=PP_ALIGN.CENTER),
    ])
    if i % 4 == 3:
        x = M
        y += 0.74
    else:
        x += cw_ + gap

card(s, M, 3.5, CW, 0.72, fill=AMBER50, pad=0.24, anchor=MSO_ANCHOR.MIDDLE, paras=[
    p([r("Kenapa batas waktu wajib? ", size=9.5, bold=True, color=AMBER),
       r("Tanpa itu, lowongan yang sudah tutup terus tampil dan portal kehilangan kepercayaan.",
         size=9.5, color=AMBER)], line=1.2),
])

statuses = [
    ("Baru", "Masuk dari feed, belum diperiksa admin."),
    ("Tayang", "Disetujui admin, tampil ke publik."),
    ("Ditolak", "Tidak layak, alasan disimpan."),
    ("Kedaluwarsa", "Otomatis setelah lewat masa berlaku."),
]
x = M
for title, desc in statuses:
    card(s, x, 4.42, 2.75, 1.15, fill=SLATE50, anchor=MSO_ANCHOR.TOP, pad=0.2, paras=[
        p(r(title, size=11, bold=True, color=NAVY), after=4),
        p(r(desc, size=8.5, color=SLATE500), line=1.2),
    ])
    x += 2.75 + 0.28

card(s, M, 5.75, CW, 0.9, fill=WHITE, line=SLATE200, pad=0.24, anchor=MSO_ANCHOR.MIDDLE, paras=[
    p([r("Terkumpul 121: ", size=9.5, bold=True, color=NAVY),
       r("67 tayang  ·  49 menunggu  ·  5 ditolak", size=9.5, color=SLATE600),
       r("   — empat status itu nyata, bukan teori.", size=9.5, color=SLATE400)]),
])
notes(s, "Empat status ini yang membedakan lowongan dari berita. Kalau ditanya kenapa tidak langsung tayang, "
         "jawab: karena agregasi bukan publikasi.")

# ---------------------------------------------------------------- 08 tempat
s = new_slide(prs)
header(s, "Kebutuhan Data · Tempat", "Satu Fitur, Tiga Jenis, Atribut Berbeda",
       "Kuliner, olahraga, dan kesehatan tidak bisa disatukan dalam satu tabel.")

tri = [
    ("Kuliner", "977 entri", ["menu", "kisaran harga", "jam buka"]),
    ("Olahraga", "245 entri", ["kapasitas", "tarif sewa", "fasilitas"]),
    ("Kesehatan", "367 entri", ["layanan 24 jam", "apotek", "kontak"]),
]
x = M
for title, count, fields in tri:
    paras = [p([r(title, size=12.5, bold=True, color=NAVY),
                r("   " + count, size=9, color=SLATE400)], after=8)]
    for f in fields:
        paras.append(p([r("· ", size=9, bold=True, color=BLUE), r(f, size=9, color=SLATE600)], after=3))
    card(s, x, 1.95, 3.75, 1.5, fill=SLATE50, anchor=MSO_ANCHOR.TOP, pad=0.24, paras=paras)
    x += 3.75 + 0.29

card(s, M, 3.55, CW, 0.8, fill=BLUE50, pad=0.24, anchor=MSO_ANCHOR.MIDDLE, paras=[
    p([r("Wajib untuk ketiganya:  ", size=9, bold=True, color=BLUE),
       r("nama · alamat · titik lokasi · jenis · jam buka · fasilitas · kontak · foto · asal data",
         size=9, color=SLATE600)], line=1.2),
])

high = [
    ("Titik lokasi", "Dipakai peta dan fitur \"tempat terdekat\". Tanpa koordinat, dua fitur itu mati."),
    ("Keterangan asal data", "Data ini milik pihak lain: ODbL, CC BY-SA, atau situs resmi. Wajib disebut."),
]
x = M
for title, desc in high:
    card(s, x, 4.65, 5.75, 1.25, fill=WHITE, line=SLATE200, anchor=MSO_ANCHOR.TOP, pad=0.26, paras=[
        p(r(title, size=11.5, bold=True, color=NAVY), after=5),
        p(r(desc, size=9, color=SLATE500), line=1.25),
    ])
    x += 5.75 + 0.33

card(s, M, 6.12, CW, 0.58, fill=SLATE50, pad=0.22, anchor=MSO_ANCHOR.MIDDLE, paras=[
    p([r("Total 1.589 entri direktori ", size=9, bold=True, color=NAVY),
       r("dari peta publik, impor berkas, situs resmi, Wikipedia, dan input admin.", size=9, color=SLATE500)]),
])
notes(s, "Tekankan: satu fitur 'tempat' sebenarnya tiga tabel karena atributnya beda. "
         "Dan dua data wajib: titik lokasi (untuk peta & terdekat) dan asal data (untuk lisensi).")

# ---------------------------------------------------------------- 09 pendukung
s = new_slide(prs)
header(s, "Kebutuhan Data · Pendukung", "Selain Tiga Modul Utama",
       "Dua data ini yang membuat sistem bisa memantau dirinya sendiri dan menerima masukan warga.")

card(s, M, 1.98, 4.55, 2.05, fill=SLATE50, anchor=MSO_ANCHOR.TOP, pad=0.26, paras=[
    p(r("Daftar sumber", size=12, bold=True, color=NAVY), after=6),
    p(r("Media dan portal yang diambil, plus catatan kapan terakhir diambil, berhasil atau gagal, "
        "dan berapa item yang didapat.", size=9, color=SLATE500), after=6, line=1.25),
    p([r("Akibatnya: ", size=9, bold=True, color=BLUE),
       r("sumber mati bisa dideteksi, bukan dibiarkan diam.", size=9, color=SLATE600)], line=1.2),
])
card(s, M, 4.2, 4.55, 1.72, fill=SLATE50, anchor=MSO_ANCHOR.TOP, pad=0.26, paras=[
    p(r("Ulasan warga", size=12, bold=True, color=NAVY), after=6),
    p(r("Rating, komentar, dan foto. Satu-satunya data yang dibuat sendiri oleh pengguna.", size=9,
        color=SLATE500), after=6, line=1.25),
    p([r("Jumlah saat ini: ", size=9, color=SLATE600), r("10 ulasan", size=9, bold=True, color=NAVY)], line=1.2),
])

rows = [
    ("Berita", "judul · ringkasan · tanggal · alamat asli", "108"),
    ("Lowongan", "posisi · perusahaan · batas waktu · status", "121"),
    ("Tempat", "nama · titik lokasi · jenis · asal data", "1.589"),
    ("Ulasan", "rating · komentar · foto", "10"),
    ("Sumber", "alamat feed · status kesehatan", "24"),
]
tx, ty = 5.65, 1.98
tw = [1.55, 3.9, 1.0]
card(s, tx, ty, sum(tw), 0.46, fill=NAVY, pad=0.16, anchor=MSO_ANCHOR.MIDDLE, paras=[])
add_text(s, tx + 0.18, ty + 0.1, tw[0], 0.26, [p(r("FITUR", size=8, bold=True, color=SLATE300))])
add_text(s, tx + tw[0] + 0.1, ty + 0.1, tw[1], 0.26, [p(r("DATA WAJIB", size=8, bold=True, color=SLATE300))])
add_text(s, tx + tw[0] + tw[1] + 0.1, ty + 0.1, tw[2], 0.26,
         [p(r("JUMLAH", size=8, bold=True, color=SLATE300), align=PP_ALIGN.RIGHT)])

ry = ty + 0.52
for i, (fitur, data, jumlah) in enumerate(rows):
    if i % 2 == 0:
        add_shape(s, MSO_SHAPE.RECTANGLE, tx, ry - 0.06, sum(tw), 0.62, fill=SLATE50)
    add_text(s, tx + 0.18, ry + 0.05, tw[0], 0.32, [p(r(fitur, size=9.5, bold=True, color=NAVY))])
    add_text(s, tx + tw[0] + 0.1, ry + 0.05, tw[1], 0.32, [p(r(data, size=9, color=SLATE600))])
    add_text(s, tx + tw[0] + tw[1] + 0.1, ry + 0.05, tw[2], 0.32,
             [p(r(jumlah, size=9.5, bold=True, color=BLUE), align=PP_ALIGN.RIGHT)])
    ry += 0.72

card(s, M, 6.12, CW, 0.58, fill=BLUE50, pad=0.22, anchor=MSO_ANCHOR.MIDDLE, paras=[
    p(r("Hasil tahap ini: daftar kebutuhan data per fitur — bukan kode, bukan tabel.", size=9,
        bold=True, color=BLUE)),
])
notes(s, "Tabel ini ringkasan yang bisa dibawa ke slide tanya-jawab. Kalau ditanya 'datanya berapa', "
         "jawab dari baris ini, bukan mengarang.")

# ---------------------------------------------------------------- 10 pembatas 2
s = new_slide(prs, dark=True)
add_shape(s, MSO_SHAPE.RECTANGLE, 0, 0, 0.16, H, fill="B45309")
add_text(s, M, 1.95, 2.4, 1.7, [p(r("02", size=88, bold=True, color="B45309"))])
add_text(s, M, 3.45, 10.9, 0.9, [p(r("Batasan Pengumpulan Data", size=38, bold=True, color=WHITE))])
add_text(s, M, 4.45, 9.8, 0.7,
         [p(r("Sampai mana data boleh dan mampu diambil — dan apa penanganannya.",
              size=14, color=SLATE300))])
add_text(s, M, 5.55, 9.0, 0.4, [p(r("BAGIAN 2 DARI 3", size=9, bold=True, color=SLATE500))])
notes(s, "Bagian ini yang paling sering bikin gugup. Sikapnya: sebut batasan lebih dulu, "
         "lalu langsung penanganannya. Jangan menunggu ditanya.")

# ---------------------------------------------------------------- 11 legal
s = new_slide(prs)
header(s, "02 · Batasan Pengumpulan Data", "Bukan Kegagalan, Tapi Keputusan",
       "Setiap batasan diikuti satu kalimat: maka yang kami lakukan adalah ...")

legal = [
    ("Hak cipta", "Isi artikel tidak disalin.",
     "Yang disimpan hanya ringkasan dan tautan ke sumber asli."),
    ("Aturan pemilik situs", "Halaman yang dilarang tidak diambil.",
     "Aturan situs dibaca lebih dulu; kalau melarang, dilewati."),
    ("Ketentuan Google Maps", "Bukan salin massal.",
     "Hanya penanda tempat yang disimpan; sisanya diambil saat halaman dibuka."),
    ("Kewajiban atribusi", "Asal data selalu disebut.",
     "ODbL untuk peta publik, CC BY-SA untuk Wikipedia, URL untuk situs resmi."),
]
positions = [(M, 1.98), (6.85, 1.98), (M, 4.12), (6.85, 4.12)]
for (x, y), (title, bold, desc) in zip(positions, legal):
    card(s, x, y, 5.75, 1.95, fill=SLATE50, anchor=MSO_ANCHOR.TOP, pad=0.28, paras=[
        p(r(title, size=12, bold=True, color=NAVY), after=6),
        p(r(bold, size=10, bold=True, color=BLUE), after=4, line=1.2),
        p(r(desc, size=9, color=SLATE500), line=1.25),
    ])

card(s, M, 6.25, CW, 0.55, fill=AMBER50, pad=0.22, anchor=MSO_ANCHOR.MIDDLE, paras=[
    p([r("Kalau ditanya \"ini legal?\": ", size=9, bold=True, color=AMBER),
       r("jawabannya ada di empat poin ini, bukan di penjelasan panjang.", size=9, color=AMBER)]),
])
notes(s, "Urutan menjawab: (1) tidak menyalin isi, (2) menghormati aturan situs, "
         "(3) tidak mengambil data Google secara massal, (4) selalu mencantumkan atribusi.")

# ---------------------------------------------------------------- 12 teknis
s = new_slide(prs)
header(s, "02 · Batasan Pengumpulan Data", "Tujuh Keterbatasan Nyata",
       "Semuanya pernah terjadi di sistem ini — bukan asumsi.")

tech = [
    ("Link Google News", "Alamatnya tidak bisa dibuka dari server.", "Hanya judul + cuplikan."),
    ("Cuplikan feed", "Feed hanya memberi ringkasan pendek.", "Isi artikel diambil bila diizinkan."),
    ("Isi artikel", "Teks dipotong agar tidak kepanjangan.", "Cukup untuk ringkasan pembuka."),
    ("Batas per sumber", "Maksimal 20 item sekali proses.", "Pembaruan berjalan berkala."),
    ("Server peta publik", "Sering timeout saat sibuk.", "Dicoba ke beberapa server lain."),
    ("Kuota AI gratis", "Sering menolak karena kuota.", "Dicoba ulang, lalu pakai cuplikan."),
    ("Kunci Google", "Belum diisi.", "Rating & foto live belum tampil."),
]
x, y = M, 1.98
bw = (CW - 3 * 0.25) / 4
for i, (title, problem, fix) in enumerate(tech[:4]):
    card(s, x, y, bw, 2.0, fill=SLATE50, anchor=MSO_ANCHOR.TOP, pad=0.22, paras=[
        p(r(title, size=11, bold=True, color=NAVY), after=7),
        p(r(problem, size=8.5, color=SLATE500), after=6, line=1.2),
        p([r("→ ", size=8.5, bold=True, color=EMERALD), r(fix, size=8.5, color=EMERALD)], line=1.2),
    ])
    x += bw + 0.25

start = M + (CW - (3 * bw + 2 * 0.25)) / 2
x = start
for title, problem, fix in tech[4:]:
    card(s, x, 4.12, bw, 2.0, fill=SLATE50, anchor=MSO_ANCHOR.TOP, pad=0.22, paras=[
        p(r(title, size=11, bold=True, color=NAVY), after=7),
        p(r(problem, size=8.5, color=SLATE500), after=6, line=1.2),
        p([r("→ ", size=8.5, bold=True, color=EMERALD), r(fix, size=8.5, color=EMERALD)], line=1.2),
    ])
    x += bw + 0.25

notes(s, "Jangan hafal semuanya. Cukup tiga yang paling mungkin ditanya: link Google News, "
         "batas 20 item, dan kuota AI. Sisanya bisa dibaca dari slide.")

# ---------------------------------------------------------------- 13 wilayah waktu
s = new_slide(prs)
header(s, "02 · Batasan Pengumpulan Data", "Wilayah dan Waktu",
       "Dua batasan yang paling sering ditanya karena menyangkut cakupan penelitian.")

card(s, M, 1.98, 5.75, 2.5, fill=SLATE50, anchor=MSO_ANCHOR.TOP, pad=0.28, paras=[
    p(r("Wilayah", size=12.5, bold=True, color=NAVY), after=8),
    p(r("·  Pengambilan dibatasi hanya Kota Tangerang Selatan.", size=9, color=SLATE600), after=5, line=1.25),
    p(r("·  Kecamatan ditentukan dari titik terdekat, bukan batas resmi — tempat di perbatasan bisa salah.",
        size=9, color=SLATE600), after=5, line=1.25),
    p(r("·  Berita disaring kata kunci: berita kota lain bisa ikut, berita Tangsel tanpa nama wilayah bisa terbuang.",
        size=9, color=SLATE600), line=1.25),
])
card(s, 6.85, 1.98, 5.75, 2.5, fill=SLATE50, anchor=MSO_ANCHOR.TOP, pad=0.28, paras=[
    p(r("Waktu", size=12.5, bold=True, color=NAVY), after=8),
    p(r("·  Data adalah kondisi saat diambil, bukan riwayat.", size=9, color=SLATE600), after=5, line=1.25),
    p(r("·  Berita sebelum sistem berjalan tidak akan pernah masuk.", size=9, color=SLATE600), after=5, line=1.25),
    p(r("·  Lowongan punya masa berlaku dan otomatis ditandai kedaluwarsa.", size=9, color=SLATE600), line=1.25),
])

card(s, M, 4.7, CW, 1.15, fill=AMBER50, anchor=MSO_ANCHOR.TOP, pad=0.26, paras=[
    p(r("Catatan operasional yang harus disebut", size=10.5, bold=True, color=AMBER), after=5),
    p(r("Pengambilan lowongan dan penandaan kedaluwarsa masih dijalankan manual, belum terjadwal otomatis seperti berita.",
        size=9, color=AMBER), line=1.2),
])

jadwal = [("Berita", "tiap jam"), ("Situs resmi", "mingguan"), ("Wikipedia", "bulanan"), ("Lowongan", "manual")]
x = M
jw = (CW - 3 * 0.25) / 4
for name, when in jadwal:
    card(s, x, 6.05, jw, 0.72, fill=WHITE, line=SLATE200, pad=0.18, anchor=MSO_ANCHOR.MIDDLE, paras=[
        p([r(name + "  ", size=9, bold=True, color=NAVY), r(when, size=9, color=SLATE500)]),
    ])
    x += jw + 0.25
notes(s, "Kalau ditanya 'kenapa hanya Tangsel', jawab: ruang lingkup penelitian. "
         "Kalau ditanya 'kecamatan kok bisa salah', jawab: karena batas resmi tidak tersedia, "
         "dipakai titik terdekat, dan admin bisa mengoreksi.")

# ---------------------------------------------------------------- 14 kualitas
s = new_slide(prs)
header(s, "02 · Batasan Pengumpulan Data", "Angka yang Harus Disebut Apa Adanya",
       "Menyebut batasan sendiri lebih kuat daripada mengklaim sempurna.")

stat(s, M, 1.98, 2.75, 1.6, "49", "lowongan belum dikurasi", "menunggu pemeriksaan admin", AMBER, AMBER50)
stat(s, M + 3.03, 1.98, 2.75, 1.6, "977", "menu masih contoh", "berlabel \"Contoh (demo)\"", AMBER, AMBER50)
stat(s, M + 6.06, 1.98, 2.75, 1.6, "0", "deskripsi lanjutan", "pipeline ada, belum dijalankan", SLATE500, SLATE50)
stat(s, M + 9.09, 1.98, 2.74, 1.6, "10", "ulasan warga", "belum cukup untuk kesimpulan", SLATE500, SLATE50)

rows = [
    ("49 lowongan belum tayang", "Justru bukti penyaringan bekerja, bukan kegagalan."),
    ("977 menu contoh", "Diberi label jelas; bukan hasil survei dan akan diganti."),
    ("0 deskripsi lanjutan", "Kemampuannya ada, tinggal dijalankan."),
    ("10 ulasan", "Data primer masih minim; disajikan apa adanya."),
]
y = 3.85
for title, desc in rows:
    card(s, M, y, CW, 0.56, fill=WHITE, line=SLATE200, pad=0.2, anchor=MSO_ANCHOR.MIDDLE, paras=[
        p([r(title, size=9.5, bold=True, color=NAVY),
           r("   —   " + desc, size=9, color=SLATE500)]),
    ])
    y += 0.66

card(s, M, 6.46, CW, 0.5, fill=BLUE50, pad=0.18, anchor=MSO_ANCHOR.MIDDLE, paras=[
    p(r("Semua angka di slide ini bisa dicek langsung di sistem.", size=9, bold=True, color=BLUE)),
])
notes(s, "Jangan defensif saat menyebut angka ini. Faktanya: 49 yang belum tayang membuktikan "
         "ada proses kurasi, bukan asal tayang.")

# ---------------------------------------------------------------- 15 pembatas 3
s = new_slide(prs, dark=True)
add_shape(s, MSO_SHAPE.RECTANGLE, 0, 0, 0.16, H, fill="047857")
add_text(s, M, 1.95, 2.4, 1.7, [p(r("03", size=88, bold=True, color="047857"))])
add_text(s, M, 3.45, 10.9, 0.9, [p(r("Teknik Pengumpulan Data", size=38, bold=True, color=WHITE))])
add_text(s, M, 4.45, 9.8, 0.7,
         [p(r("Komponen apa yang mengambil, dan bagaimana alur tiap fiturnya.",
              size=14, color=SLATE300))])
add_text(s, M, 5.55, 9.0, 0.4, [p(r("BAGIAN 3 DARI 3", size=9, bold=True, color=SLATE500))])
notes(s, "Bagian ini yang paling teknis. Sebut nama komponen hanya di sini, "
         "karena inilah yang ditanyakan kalau penguji minta lihat kode.")

# ---------------------------------------------------------------- 16 komponen
s = new_slide(prs)
header(s, "03 · Teknik Pengumpulan Data", "Komponen Inti dan Fungsinya",
       "Nama-nama ini yang muncul kalau penguji minta lihat kode.")

comps = [
    ("NewsFetcher", "Mengatur seluruh alur berita: unduh, saring, ringkas, simpan."),
    ("LowonganFetcher", "Alur lowongan: dedup ganda, ekstraksi field, saring wilayah."),
    ("FeedParser", "Membaca RSS, Atom, dan RDF jadi satu bentuk seragam."),
    ("ArticleContentFetcher", "Ambil isi artikel setelah cek aturan situs, lalu dipotong."),
    ("AIEkstraksiLowongan", "Ubah teks lowongan jadi kolom: posisi, syarat, cara melamar."),
    ("GeminiClient", "Satu pintu ke AI: percobaan ulang, batas waktu, jeda, hemat token."),
    ("OsmImporter", "Impor tempat dari peta publik; data lama tidak ditimpa."),
    ("Categorizer", "Tentukan kategori dan kecamatan dari kata kunci."),
]
x, y = M, 1.98
bw = (CW - 3 * 0.22) / 4
for i, (name, desc) in enumerate(comps):
    card(s, x, y, bw, 1.95, fill=SLATE50, anchor=MSO_ANCHOR.TOP, pad=0.2, paras=[
        p(r(name, size=9.5, bold=True, color=NAVY, font=MONO), after=7),
        p(r(desc, size=8.5, color=SLATE500), line=1.22),
    ])
    if i % 4 == 3:
        x = M
        y += 2.1
    else:
        x += bw + 0.22

notes(s, "Jangan baca satu per satu. Sebut tiga yang paling penting: NewsFetcher (berita), "
         "LowonganFetcher (lowongan), OsmImporter (tempat). Sisanya penjelas.")

# ---------------------------------------------------------------- 17 alur berita
s = new_slide(prs)
header(s, "03 · Teknik · Berita", "Alur Pengambilan Berita",
       "Perintah: berita:fetch — berjalan otomatis tiap jam.")

flow_row(s, 1.9, [
    ("1", "9 feed RSS", "Media lokal yang menyediakan feed.", False),
    ("2", "NewsFetcher", "Unduh, batasi 20 item, cek duplikat.", True),
    ("3", "FeedParser", "Seragamkan RSS, Atom, dan RDF.", True),
    ("4", "Dedup alamat", "Lewati yang sudah pernah masuk.", False),
])
flow_row(s, 3.45, [
    ("5", "ArticleContentFetcher", "Cek aturan situs, ambil teks, potong.", True),
    ("6", "AISummarizer", "Ringkas; gagal? pakai cuplikan feed.", True),
    ("7", "Categorizer", "Tentukan kategori & kecamatan.", True),
    ("8", "Simpan berita", "Status tayang, catat status sumber.", False),
])

card(s, M, 5.15, 5.75, 0.95, fill=BLUE50, pad=0.24, anchor=MSO_ANCHOR.MIDDLE, paras=[
    p([r("Kalau AI gagal, berita tetap masuk. ", size=9, bold=True, color=BLUE),
       r("Ringkasan memakai cuplikan feed.", size=9, color=SLATE600)], line=1.2),
])
card(s, 6.85, 5.15, 5.75, 0.95, fill=SLATE50, pad=0.24, anchor=MSO_ANCHOR.MIDDLE, paras=[
    p([r("Batas per sumber: 20 item. ", size=9, bold=True, color=NAVY),
       r("Supaya proses tidak kelamaan.", size=9, color=SLATE500)], line=1.2),
])
card(s, M, 6.22, CW, 0.5, fill=WHITE, line=SLATE200, pad=0.2, anchor=MSO_ANCHOR.MIDDLE, paras=[
    p([r("Hasil: 108 berita dari 9 sumber ", size=9, bold=True, color=NAVY),
       r("— 105 ringkasan dari feed, 3 suntingan editor.", size=9, color=SLATE500)]),
])
notes(s, "Alur berita: feed diunduh, diurai, dibuang duplikatnya, isi artikel diambil kalau diizinkan, "
         "lalu diringkas. Titik paling penting: kegagalan AI tidak menghentikan berita masuk.")

# ---------------------------------------------------------------- 18 alur lowongan
s = new_slide(prs)
header(s, "03 · Teknik · Lowongan", "Alur Pengambilan Lowongan",
       "Perintah: lowongan:fetch — hasilnya belum tayang sampai diperiksa admin.")

flow_row(s, 1.88, [
    ("1", "15 feed RSS", "Google News & Bing News.", False),
    ("2", "LowonganFetcher", "Unduh, batasi, rapikan judul.", True),
    ("3", "FeedParser", "Seragamkan format feed.", True),
    ("4", "Dedup ganda", "Alamat asli + sidik jari judul.", False),
])
flow_row(s, 3.36, [
    ("5", "AIEkstraksiLowongan", "Teks jadi kolom; gagal? pola judul.", True),
    ("6", "LowonganClassifier", "Kategori, kecamatan, jenis.", True),
    ("7", "LowonganWilayah", "Saring yang bukan Tangsel.", True),
    ("8", "Simpan \"baru\"", "Menunggu kurasi admin.", False),
])

card(s, M, 4.95, CW, 0.95, fill=AMBER50, pad=0.24, anchor=MSO_ANCHOR.MIDDLE, paras=[
    p([r("Kurasi admin: ", size=10, bold=True, color=AMBER),
       r("disetujui → tayang   ·   ditolak → alasan disimpan   ·   lewat masa berlaku → kedaluwarsa otomatis",
         size=9, color=AMBER)], line=1.2),
])

nums = [("121", "masuk"), ("67", "tayang"), ("49", "menunggu"), ("5", "ditolak")]
x = M
nw = (CW - 3 * 0.25) / 4
for value, label in nums:
    card(s, x, 6.05, nw, 0.75, fill=SLATE50, pad=0.18, anchor=MSO_ANCHOR.MIDDLE, paras=[
        p([r(value + "  ", size=14, bold=True, color=NAVY), r(label, size=9, color=SLATE500)]),
    ])
    x += nw + 0.25
notes(s, "Beda dengan berita: ada dedup dua lapis (alamat dan sidik jari judul), "
         "lalu hasilnya sengaja ditahan di status baru. Angka 121/67/49/5 menunjukkan prosesnya nyata.")

# ---------------------------------------------------------------- 19 alur tempat
s = new_slide(prs)
header(s, "03 · Teknik · Tempat", "Enam Jalur Pengambilan Tempat",
       "Semua bertemu di satu titik: dedup berdasarkan penanda peta, kecamatan dari titik terdekat.")

tempat = [
    ("places:sync", "Simpan dari peta publik (OpenStreetMap).",
     "Dedup penanda peta; entri lama tidak ditimpa.", "784 kuliner"),
    ("gmaps:import", "Impor berkas JSON hasil ekspor Google Maps.",
     "Mode \"perkaya\": cocokkan nama + radius 150 m.", "193 entri"),
    ("info:fetch", "Ambil foto, kontak, dan menu dari situs resmi.",
     "Hormati aturan situs; maksimal 25 situs sekali jalan.", "112 website"),
    ("wiki:sync", "Lengkapi data rumah sakit dari Wikipedia.",
     "Hanya daftar resmi + paragraf pembuka, CC BY-SA.", "Rumah sakit"),
    ("places:match", "Cocokkan tempat dengan penanda Google.",
     "Hanya penanda yang disimpan; rating diambil live.", "203 penanda"),
    ("Input admin", "Tambahan dan koreksi manual.",
     "Koreksi admin tidak pernah ditimpa proses otomatis.", "193 manual"),
]
x, y = M, 1.98
bw = (CW - 2 * 0.28) / 3
for i, (cmd, desc, note, result) in enumerate(tempat):
    card(s, x, y, bw, 1.95, fill=SLATE50, anchor=MSO_ANCHOR.TOP, pad=0.22, paras=[
        p(r(cmd, size=9.5, bold=True, color=BLUE, font=MONO), after=6),
        p(r(desc, size=8.5, color=SLATE600), after=5, line=1.2),
        p(r(note, size=8, color=SLATE500), after=6, line=1.2),
        p(r(result, size=8, bold=True, color=EMERALD)),
    ])
    if i % 3 == 2:
        x = M
        y += 2.1
    else:
        x += bw + 0.28

card(s, M, 6.2, CW, 0.5, fill=BLUE50, pad=0.2, anchor=MSO_ANCHOR.MIDDLE, paras=[
    p([r("Total 1.589 entri: ", size=9, bold=True, color=BLUE),
       r("977 kuliner · 245 fasilitas olahraga · 367 layanan kesehatan.", size=9, color=SLATE600)]),
])
notes(s, "Enam jalur ini sering bikin kagum, tapi jelaskan singkat saja: peta publik, impor berkas, "
         "situs resmi, Wikipedia, penanda Google, dan input admin. Dedup-nya satu: penanda peta.")

# ---------------------------------------------------------------- 20 istilah 1
s = new_slide(prs)
header(s, "03 · Teknik · Istilah", "Istilah Sumber dan Pengambilan",
       "Dijelaskan satu baris, supaya tidak berhenti saat ditanya.")

terms1 = [
    ("RSS / feed", "Daftar artikel terbaru dalam format standar, dibuat untuk dibaca mesin."),
    ("Parser", "Pembaca yang mengubah teks mentah menjadi data terstruktur."),
    ("Dedup", "Membuang data kembar supaya tidak ada berita atau lowongan dobel."),
    ("Hash", "Sidik jari digital sebuah teks; dipakai menangkap lowongan sama dari dua sumber."),
    ("Slug", "Versi judul untuk alamat halaman, tanpa spasi dan tanda baca."),
    ("Scraping", "Mengambil isi halaman web; di sini dibatasi hanya halaman yang mengizinkan."),
    ("robots.txt", "Aturan pemilik situs soal halaman yang boleh diambil mesin."),
    ("User-Agent", "Identitas yang dikirim saat mengakses situs, supaya situs tahu siapa yang datang."),
    ("Snapshot", "Data pada satu titik waktu; tidak menyimpan riwayat."),
]
y = 1.9
for i, (term, meaning) in enumerate(terms1):
    if i % 2 == 0:
        add_shape(s, MSO_SHAPE.RECTANGLE, M, y - 0.03, CW, 0.5, fill=SLATE50)
    add_text(s, M + 0.18, y + 0.06, 2.35, 0.3, [p(r(term, size=9.5, bold=True, color=NAVY, font=MONO))])
    add_text(s, M + 2.6, y + 0.07, CW - 2.75, 0.3, [p(r(meaning, size=9, color=SLATE600))])
    y += 0.52

notes(s, "Tidak perlu dihafal. Kalau ditanya salah satu, baca dari slide ini dengan tenang, "
         "lalu kaitkan ke komponen yang memakainya.")

# ---------------------------------------------------------------- 21 istilah 2
s = new_slide(prs)
header(s, "03 · Teknik · Istilah", "Istilah Wilayah, AI, dan Sistem",
       "Istilah teknis yang paling sering muncul saat tanya-jawab.")

terms2 = [
    ("API", "Pintu resmi untuk mengambil data dari layanan lain."),
    ("JSON", "Format data pasangan kunci–nilai; dipakai untuk impor dan jawaban AI."),
    ("Bounding box", "Kotak koordinat yang membatasi area pencarian, di sini wilayah Tangsel."),
    ("Haversine", "Rumus jarak dua titik koordinat; dasar \"tempat terdekat\"."),
    ("Centroid", "Titik pusat wilayah; dipakai menentukan kecamatan terdekat."),
    ("Kurasi", "Pemeriksaan manusia sebelum data tayang."),
    ("Retry & throttle", "Mengulang permintaan gagal, dengan jeda agar tidak menabrak kuota."),
    ("Token", "Satuan teks yang dihitung AI; makin banyak, makin lambat."),
    ("Thinking budget", "Jatah berpikir model sebelum menjawab; diset 0 karena tugasnya singkat."),
    ("Skema JSON", "Bentuk keluaran yang diwajibkan, supaya AI tidak mengarang kolom."),
    ("Halusinasi", "AI mengarang fakta; dicegah dengan skema, aturan ketat, dan kurasi manusia."),
]
y = 1.82
for i, (term, meaning) in enumerate(terms2):
    if i % 2 == 0:
        add_shape(s, MSO_SHAPE.RECTANGLE, M, y - 0.03, CW, 0.44, fill=SLATE50)
    add_text(s, M + 0.18, y + 0.05, 2.35, 0.28, [p(r(term, size=9.5, bold=True, color=NAVY, font=MONO))])
    add_text(s, M + 2.6, y + 0.06, CW - 2.75, 0.28, [p(r(meaning, size=9, color=SLATE600))])
    y += 0.45

notes(s, "Dua istilah yang paling mungkin ditanya: bounding box (kenapa hanya Tangsel) "
         "dan halusinasi (bagaimana AI dicegah mengarang).")

# ---------------------------------------------------------------- 22 penutup
s = new_slide(prs, dark=True)
add_shape(s, MSO_SHAPE.RECTANGLE, 0, 0, 0.16, H, fill=BLUE)
add_text(s, M, 0.75, CW, 0.3, [p(r("RINGKASAN", size=9.5, bold=True, color=BLUE300))])
add_text(s, M, 1.05, 10.5, 0.6, [p(r("Tiga Bagian, Satu Tarikan Napas", size=30, bold=True, color=WHITE))])

stats = [
    ("24", "sumber aktif", "9 berita · 15 lowongan"),
    ("108", "berita", "terkumpul otomatis"),
    ("121", "lowongan", "67 tayang setelah kurasi"),
    ("1.589", "entri direktori", "977 · 245 · 367"),
    ("10", "ulasan warga", "data primer"),
    ("152", "pengujian", "554 pemeriksaan"),
]
x = M
bw = (CW - 5 * 0.22) / 6
for value, label, sub in stats:
    card(s, x, 2.05, bw, 1.55, fill="16233C", pad=0.18, anchor=MSO_ANCHOR.MIDDLE, paras=[
        p(r(value, size=22, bold=True, color=WHITE), after=3),
        p(r(label, size=9, bold=True, color=BLUE300), after=2),
        p(r(sub, size=7.5, color=SLATE400), line=1.15),
    ])
    x += bw + 0.22

card(s, M, 4.0, CW, 1.55, fill="16233C", pad=0.35, anchor=MSO_ANCHOR.MIDDLE, paras=[
    p(r("\"Analisis kebutuhan menentukan data apa. Teknik menentukan komponen mana yang mengambil. "
        "Batasan menentukan sejauh mana boleh diambil — dan apa yang dilakukan saat tidak bisa.\"",
        size=13, bold=True, color=WHITE), after=6, line=1.35),
    p(r("Ketiganya konsisten, dan tiap batasan punya penanganannya sendiri.", size=9.5, color=SLATE400)),
])

add_text(s, M, 5.95, 7.5, 0.6, [
    p(r("TANGSEL LIA", size=9, bold=True, color=SLATE400), after=2),
    p(r("Platform Informasi Lokal Tangerang Selatan", size=8.5, color=SLATE500)),
])
add_text(s, W - M - 2.6, 5.95, 2.6, 0.6, [
    p(r("Terima kasih", size=15, bold=True, color=BLUE300), align=PP_ALIGN.RIGHT),
])
notes(s, "Tutup dengan tiga kalimat ini, jangan menambah. Kalau ada waktu, sebut angka 121 → 67 "
         "sebagai bukti kurasi berjalan.")

prs.save(OUT)
print("tersimpan:", OUT)
print("jumlah slide:", len(prs.slides))
