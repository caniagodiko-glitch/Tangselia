"""
monitor.py - Pantau progress scraping secara real-time
Jalankan di terminal terpisah: python monitor.py
"""
import json, time, os, sys
from pathlib import Path
from datetime import datetime

FILES = {
    "🍜 Kuliner ": "hasil_kuliner.json",
    "⚽ Olahraga": "hasil_olahraga.json",
    "🏥 Kesehatan": "hasil_kesehatan.json",
}

def hitung(path):
    try:
        data = json.loads(Path(path).read_text(encoding="utf-8"))
        last = data[-1]["nama"] if data else "-"
        return len(data), last
    except Exception:
        return 0, "-"

def clear():
    os.system("cls" if os.name == "nt" else "clear")

print("Monitoring scraping... (Ctrl+C untuk berhenti)\n")

try:
    while True:
        clear()
        total = 0
        print(f"{'='*55}")
        print(f"  PROGRESS SCRAPING GMAPS — {datetime.now().strftime('%H:%M:%S')}")
        print(f"{'='*55}")
        for label, file in FILES.items():
            n, last = hitung(file)
            total += n
            status = "🟢 Berjalan" if Path(file).exists() else "⏳ Menunggu"
            print(f"\n  {label} [{status}]")
            print(f"    Terkumpul : {n} tempat")
            print(f"    Terakhir  : {last[:45]}")
        print(f"\n{'─'*55}")
        print(f"  TOTAL       : {total} tempat")
        print(f"{'='*55}")
        print("\n  Refresh tiap 10 detik. Ctrl+C untuk berhenti.")
        time.sleep(10)
except KeyboardInterrupt:
    print("\nMonitor dihentikan.")
