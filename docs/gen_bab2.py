# -*- coding: utf-8 -*-
"""Bangun 'BAB 2 - TANGSEL LIA.docx' dari 'BAB 2 - TANGSEL LIA.md'.

Markdown menjadi satu-satunya sumber konten. Format docx mengikuti BAB 1:
Times New Roman 12, spasi 1.5, margin T4 L4 R3 B3 cm, tabel Table Grid.
"""
import io
import os
import re
import sys

from docx import Document
from docx.enum.table import WD_TABLE_ALIGNMENT
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.shared import Cm, Pt

DOCS = os.path.dirname(os.path.abspath(__file__))
SRC_MD = os.path.join(DOCS, "BAB 2 - TANGSEL LIA.md")
OUT_MD = SRC_MD
OUT_DOCX = os.path.join(DOCS, "BAB 2 - TANGSEL LIA.docx")
FONT = "Times New Roman"
LEBAR_TEKS = 14.0  # cm, 21 - 4 - 3

TOKEN = re.compile(r"(\*\*.+?\*\*|\*[^*]+?\*)", re.S)
NOMOR = re.compile(r"^(\d+)\.\s+(.*)$")


def parse_inline(text):
    """-> list of (teks, bold, italic)."""
    parts = []
    for chunk in TOKEN.split(text):
        if not chunk:
            continue
        if chunk.startswith("**") and chunk.endswith("**") and len(chunk) > 4:
            parts.append((chunk[2:-2], True, False))
        elif chunk.startswith("*") and chunk.endswith("*") and len(chunk) > 2:
            parts.append((chunk[1:-1], False, True))
        else:
            parts.append((chunk, False, False))
    return parts


# --------------------------------------------------------------------------
# Pembacaan markdown
# --------------------------------------------------------------------------
def baca_blok(path):
    """Ubah markdown menjadi daftar blok: ('h1'|'h2'|'p'|'li'|'tabel'|'caption', isi)."""
    with io.open(path, encoding="utf-8") as f:
        baris = f.read().splitlines()

    blok = []
    i = 0

    while i < len(baris):
        teks = baris[i].rstrip()

        if not teks.strip():
            i += 1
            continue

        if teks.startswith("# "):
            blok.append(("h1", teks[2:].strip()))
            i += 1
            continue

        if teks.startswith("## "):
            blok.append(("h2", teks[3:].strip()))
            i += 1
            continue

        if teks.startswith("### "):
            blok.append(("h3", teks[4:].strip()))
            i += 1
            continue

        if teks.startswith("|"):
            baris_tabel = []

            while i < len(baris) and baris[i].strip().startswith("|"):
                baris_tabel.append(baris[i].strip())
                i += 1

            blok.append(("tabel", baris_tabel))
            continue

        cocok = NOMOR.match(teks.strip())

        if cocok:
            blok.append(("li", (int(cocok.group(1)), cocok.group(2).strip())))
            i += 1
            continue

        if teks.strip().startswith("**Tabel "):
            blok.append(("caption", teks.strip()))
            i += 1
            continue

        blok.append(("p", teks.strip()))
        i += 1

    return blok


def sel_tabel(baris_tabel):
    """Ambil baris sel, lewati baris pemisah |---|---|."""
    hasil = []

    for baris in baris_tabel:
        isi = [s.strip() for s in baris.strip().strip("|").split("|")]

        if all(re.fullmatch(r":?-{2,}:?", s) for s in isi if s != ""):
            continue

        hasil.append(isi)

    return hasil


# --------------------------------------------------------------------------
# Docx
# --------------------------------------------------------------------------
def force_font(run, size=12, bold=None, italic=None):
    run.font.name = FONT
    run.font.size = Pt(size)
    if bold is not None:
        run.font.bold = bold
    if italic is not None:
        run.font.italic = italic
    rpr = run._element.get_or_add_rPr()
    rfonts = rpr.get_or_add_rFonts()
    for attr in ("w:ascii", "w:hAnsi", "w:eastAsia", "w:cs"):
        rfonts.set(qn(attr), FONT)


def new_doc():
    doc = Document()
    style = doc.styles["Normal"]
    style.font.name = FONT
    style.font.size = Pt(12)
    pf = style.paragraph_format
    pf.line_spacing = 1.5
    pf.space_before = Pt(0)
    pf.space_after = Pt(6)
    pf.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
    for s in doc.sections:
        s.top_margin = Cm(4)
        s.left_margin = Cm(4)
        s.right_margin = Cm(3)
        s.bottom_margin = Cm(3)
    return doc


def add_page_number(paragraph):
    paragraph.alignment = WD_ALIGN_PARAGRAPH.CENTER
    run = paragraph.add_run()
    force_font(run)
    r = run._element
    begin = OxmlElement("w:fldChar")
    begin.set(qn("w:fldCharType"), "begin")
    instr = OxmlElement("w:instrText")
    instr.set(qn("xml:space"), "preserve")
    instr.text = "PAGE"
    sep = OxmlElement("w:fldChar")
    sep.set(qn("w:fldCharType"), "separate")
    end = OxmlElement("w:fldChar")
    end.set(qn("w:fldCharType"), "end")
    for el in (begin, instr, sep, end):
        r.append(el)


def para_rich(doc, text, align="justify", indent=None, space_after=6, line=1.5,
              size=12, bold_semua=False):
    p = doc.add_paragraph()
    p.alignment = {
        "justify": WD_ALIGN_PARAGRAPH.JUSTIFY,
        "center": WD_ALIGN_PARAGRAPH.CENTER,
        "left": WD_ALIGN_PARAGRAPH.LEFT,
    }[align]
    pf = p.paragraph_format
    pf.line_spacing = line
    pf.space_after = Pt(space_after)
    if indent is not None:
        pf.first_line_indent = Cm(indent)
    for chunk, bold, italic in parse_inline(text):
        run = p.add_run(chunk)
        force_font(run, size=size, bold=bold or bold_semua, italic=italic)
    return p


def tulis_tabel(doc, baris_tabel):
    sel = sel_tabel(baris_tabel)

    if not sel:
        return

    kolom = max(len(r) for r in sel)
    table = doc.add_table(rows=0, cols=kolom)
    table.style = "Table Grid"
    table.alignment = WD_TABLE_ALIGNMENT.CENTER

    for indeks, isi in enumerate(sel):
        cells = table.add_row().cells
        for posisi in range(kolom):
            teks = isi[posisi] if posisi < len(isi) else ""
            p = cells[posisi].paragraphs[0]
            p.paragraph_format.line_spacing = 1.15
            p.paragraph_format.space_after = Pt(2)
            for chunk, bold, italic in parse_inline(teks):
                r = p.add_run(chunk)
                force_font(r, size=11, bold=bold or indeks == 0, italic=italic)

    lebar = Cm(LEBAR_TEKS / kolom)
    for row in table.rows:
        for cell in row.cells:
            cell.width = lebar


def build_docx(blok):
    doc = new_doc()
    add_page_number(doc.sections[0].footer.paragraphs[0])

    for jenis, isi in blok:
        if jenis == "h1":
            para_rich(doc, isi, align="center", space_after=0, bold_semua=True)
        elif jenis == "h2":
            para_rich(doc, isi, align="left", space_after=8, bold_semua=True)
        elif jenis == "h3":
            para_rich(doc, isi, align="left", space_after=6, bold_semua=True)
        elif jenis == "caption":
            para_rich(doc, isi, align="center", space_after=10)
        elif jenis == "tabel":
            tulis_tabel(doc, isi)
            para_rich(doc, "", space_after=6)
        elif jenis == "li":
            nomor, teks = isi
            p = doc.add_paragraph()
            p.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
            pf = p.paragraph_format
            pf.line_spacing = 1.5
            pf.space_after = Pt(6)
            pf.left_indent = Cm(0.75)
            pf.first_line_indent = Cm(-0.75)
            run = p.add_run("%d.\t" % nomor)
            force_font(run)
            for chunk, bold, italic in parse_inline(teks):
                r = p.add_run(chunk)
                force_font(r, bold=bold, italic=italic)
        else:
            para_rich(doc, isi, indent=1.25)

    doc.save(OUT_DOCX)


def main():
    if not os.path.exists(SRC_MD):
        sys.exit("Sumber tidak ditemukan: %s" % SRC_MD)

    blok = baca_blok(SRC_MD)
    build_docx(blok)
    jumlah_kata = 0

    for jenis, isi in blok:
        if jenis in ("p", "h2", "h3", "caption"):
            jumlah_kata += len(isi.split())
        elif jenis == "li":
            jumlah_kata += len(isi[1].split())

    print("SUMBER ->", OUT_MD, os.path.getsize(OUT_MD), "bytes")
    print("DOCX   ->", OUT_DOCX, os.path.getsize(OUT_DOCX), "bytes")
    print("blok   :", len(blok))
    print("kata   :", jumlah_kata)


if __name__ == "__main__":
    main()
