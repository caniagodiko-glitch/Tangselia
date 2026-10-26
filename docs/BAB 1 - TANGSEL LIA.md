# BAB I

# PENDAHULUAN

## 1.1 Latar Belakang

Kota Tangerang Selatan merupakan wilayah perkotaan di bagian selatan DKI Jakarta yang berkembang pesat sebagai pusat kegiatan ekonomi, pendidikan, dan industri. Pertumbuhan populasi yang terus meningkat disertai pola hidup yang semakin urban menyebabkan karakteristik aktivitas warga menjadi semakin beragam. Kebutuhan warga tidak lagi terbatas pada kebutuhan primer, melainkan meluas hingga kebutuhan akan informasi, rekreasi (*leisure*), dan kegiatan komunitas. Seiring dengan semakin kompleksnya pola aktivitas tersebut, jenis informasi yang dibutuhkan warga juga bertambah banyak dan beragam. Kondisi ini sejalan dengan konsep kota cerdas (*smart city*) yang menempatkan pemanfaatan teknologi informasi sebagai instrumen untuk meningkatkan kualitas hidup warga dan efisiensi tata kelola perkotaan (Caragliu dkk., 2011). Namun, penerapan kebijakan *smart city* di Indonesia masih dinilai belum berkelanjutan dan cenderung berjalan secara sektoral antarinstitusi, sehingga kebutuhan informasi warga belum terlayani secara utuh (Bahari dkk., 2024).

Berdasarkan pengamatan terhadap kebutuhan warga Tangerang Selatan, informasi lokal yang paling banyak dicari dapat dikelompokkan ke dalam delapan kategori, yaitu Berita Kota, Event dan Festival, Fasilitas Olahraga, Kuliner dan Cafe, Layanan Kesehatan, UMKM Lokal, Komunitas, serta Lowongan Kerja dan Magang. Kedelapan kategori tersebut merepresentasikan kebutuhan warga yang bersifat heterogen dan mencakup dimensi informasi, rekreasi, serta layanan publik. Pada kondisi saat ini, warga harus berpindah-pindah platform seperti media sosial, peta digital, portal berita, job board nasional, hingga kanal resmi instansi untuk memenuhi kebutuhan lokal yang berbeda-beda. Informasi tersebut sebenarnya tersedia dalam jumlah yang memadai, namun tersebar dan belum terintegrasi dalam satu pengalaman pencarian yang utuh. Akibatnya, muncul beban kognitif berupa kelebihan informasi (*information overload*) yang membuat warga sulit menyaring informasi yang relevan secara efisien (Putri dkk., 2025).

Dari kondisi tersebut, setidaknya terdapat empat akar permasalahan. *Pertama*, informasi yang tersebar, di mana data dan informasi publik berceceran di berbagai platform digital yang terpisah sehingga sulit ditemukan secara utuh dalam satu titik masuk. *Kedua*, akses yang tidak praktis, karena warga terpaksa membuka banyak aplikasi berbeda untuk kebutuhan yang berbeda sehingga waktu dan tenaga terbuang percuma. *Ketiga*, pencarian yang terpisah, karena belum tersedia direktori tunggal yang memadukan berita, agenda kegiatan, tempat, dan tawaran karier dalam satu pengalaman pencarian. *Keempat*, informasi lowongan kerja yang tercecer, karena tawaran lowongan di wilayah Tangerang Selatan tersebar di job board nasional, portal lowongan beregional, dan media lokal yang berbeda-beda sehingga warga harus memantau banyak kanal sekaligus, berpindah dari satu tawaran ke tawaran lain, serta berisiko melewatkan lowongan yang masa berlakunya singkat. Dampak lanjutan dari keempat permasalahan tersebut adalah menurunnya pengalaman pencarian warga, terbuangnya produktivitas, serta lemahnya akses informasi karier yang seharusnya menjadi pintu masuk bagi pekerja dan magang di daerahnya.

Penelitian mengenai perilaku pencarian kerja daring menunjukkan bahwa mencari lowongan bukan sekadar mencocokkan profil pelamar dengan tawaran yang tersedia, melainkan merupakan masalah desain pencarian (*search design*) yang menentukan apakah informasi yang ditemukan benar-benar relevan dan dapat ditindaklanjuti (Kircher, 2020). Pada tataran implementatif, penelitian di Indonesia menunjukkan bahwa penyediaan informasi lowongan pada tingkat wilayah menuntut sistem informasi tersendiri, karena job board nasional tidak menyaring tawaran berdasarkan lokasi secara memadai sehingga pengecualian lowongan di luar daerah harus dilakukan secara manual (Novianti dkk., 2024). Di sisi lain, aplikasi super pemerintah daerah seperti Jakarta Kini (JAKI) telah membuktikan bahwa pengintegrasian berbagai layanan ke dalam satu platform mampu memperluas akses layanan warga (Ramadhan dkk., 2025). Namun, JAKI berorientasi pada layanan administratif birokrasi pemerintahan dan sangat bergantung pada tingkat literasi digital penggunanya, sehingga belum menjawab kebutuhan informasi karier dan aktivitas lokal warga secara umum.

Untuk menjawab permasalahan tersebut, penelitian ini mengusulkan pengembangan sistem informasi terintegrasi bernama TANGSEL LIA. Platform ini memungkinkan warga menemukan informasi, tempat, maupun tawaran karier lokal melalui satu pintu masuk yang sama sehingga mengurangi friksi kognitif saat mencari informasi. Sistem dirancang dengan empat pilar utama, yaitu *Information* (berita dan layanan kesehatan), *Discovery* (kuliner dan fasilitas olahraga), *Activity* (agenda kegiatan dan komunitas), serta *Employment* (lowongan kerja dan magang). Secara arsitektur, sistem dibangun sebagai dua aplikasi yang terpisah: *backend* menggunakan *framework* Laravel yang menyediakan REST API sekaligus pengelolaan basis data MySQL, dan *frontend* menggunakan Next.js dengan TypeScript serta Tailwind CSS sehingga tampilan menyesuaikan seluruh ukuran layar (*mobile-first*). Pemisahan kedua aplikasi dipilih agar tanggung jawab tiap komponen jelas, memudahkan pengembangan paralel, dan memungkinkan penskalaan tiap lapisan secara independen. Sebagai sumber data awal untuk direktori tempat, sistem memanfaatkan data OpenStreetMap (OSM) yang bersifat terbuka, sejalan dengan praktik pemanfaatan data geografis sukarela (*volunteered geographic information*) yang telah terbukti layak untuk memodelkan dan memetakan objek perkotaan (Haklay & Weber, 2008; Zhang & Pfoser, 2019).

Fitur unggulan dari sistem ini adalah modul Lowongan Kerja dan Magang yang menghimpun tawaran lowongan dari job board (LinkedIn Jobs, Glints, Karir.com) dan media lokal melalui *feed* RSS, menyaring tawaran berdasarkan penanda wilayah Tangerang Selatan, mengklasifikasikan jenis lowongan secara deterministik menjadi magang, karier, atau paruh waktu, lalu menerbitkannya melalui alur kurasi dua tahap, yaitu Ambil dari Sumber, Curasi Administrator, hingga Tayang atau Ditolak, dengan kedaluwarsa otomatis setelah tiga puluh hari. Penyaringan wilayah dilakukan berdasarkan penanda wilayah pada judul dan cuplikan lowongan, bukan berdasarkan pelacakan lokasi pengguna. Pada tahap purwarupa ini, prioritas pengembangan difokuskan pada pilar *Information*, *Discovery*, dan *Employment*, yang direalisasikan melalui modul Beranda, direktori tempat pada kategori Kuliner, Fasilitas Olahraga, serta Layanan Kesehatan, modul Berita yang dilengkapi ringkasan otomatis bertanda “Diringkas AI”, dan modul Lowongan Kerja dan Magang. Secara akademik, kontribusi penelitian ini terletak pada perancangan dan pengembangan purwarupa platform digital yang mengintegrasikan dimensi informasi lokal dengan layanan informasi karier yang terkurasi, serta pembuktian kelayakan teknisnya melalui pengujian fungsional pada masing-masing modul.

## 1.2 Rumusan Masalah

Berdasarkan latar belakang yang telah diuraikan, maka rumusan masalah dalam penelitian ini adalah sebagai berikut:

1. Bagaimana merancang dan membangun sistem informasi terintegrasi yang menghimpun serta menyajikan informasi dan aktivitas lokal Kota Tangerang Selatan yang saat ini tersebar di berbagai kanal, sehingga dapat diakses secara lebih mudah, terstruktur, dan dalam satu pengalaman pencarian oleh masyarakat?

2. Bagaimana menyediakan akses terintegrasi bagi berita, fasilitas olahraga, kuliner dan cafe, layanan kesehatan, serta lowongan kerja dan magang dalam satu platform yang komprehensif, sehingga warga tidak lagi perlu berpindah-pindah aplikasi untuk memenuhi kebutuhan lokal yang berbeda-beda?

3. Bagaimana menyediakan layanan informasi lowongan kerja dan magang di wilayah Tangerang Selatan yang terkurasi, dengan menghimpun tawaran dari job board dan media lokal, menyaringnya berdasarkan wilayah, serta menerbitkannya melalui alur status yang transparan sehingga warga dapat menemukan lowongan yang relevan tanpa menelusuri banyak kanal secara terpisah?

## 1.3 Tujuan Penelitian

Berdasarkan rumusan masalah yang telah dikemukakan, maka tujuan dari pelaksanaan Tugas Akhir ini adalah:

1. Mempermudah masyarakat memperoleh informasi dan aktivitas lokal Tangerang Selatan yang tersebar di berbagai kanal melalui penyajian kembali dalam satu titik masuk yang terpusat, terstruktur, serta mudah diakses dan dicari.

2. Menyatukan akses berita, fasilitas olahraga, kuliner dan cafe, layanan kesehatan, serta lowongan kerja dan magang dalam satu platform yang komprehensif, responsif, dan mudah digunakan pada berbagai ukuran layar.

3. Menghasilkan layanan informasi lowongan kerja dan magang di wilayah Tangerang Selatan yang terkurasi, terperinci, serta mudah disaring berdasarkan jenis, bidang pekerjaan, dan kecamatan, sehingga warga dapat menemukan peluang karier yang relevan tanpa berpindah-pindah kanal.

## 1.4 Batasan Masalah

Untuk menjaga agar penelitian ini tetap fokus dan terarah, batasan masalah (ruang lingkup) yang ditetapkan adalah sebagai berikut:

1. **Ruang Lingkup Data dan Pengguna.** Data yang dikelola mencakup informasi lokal Kota Tangerang Selatan, yaitu berita kota, fasilitas olahraga, kuliner dan cafe, layanan kesehatan, serta lowongan kerja dan magang beserta status penerbitannya. Sistem membedakan dua peran pengguna utama, yaitu administrator sebagai pengelola data dan kurator lowongan, serta warga sebagai pengguna aktif yang menjelajahi informasi. Ruang lingkup data dibatasi pada informasi bersifat publik; sistem tidak mengintegrasikan sistem informasi instansi yang telah ada, tidak menyimpan data pribadi sensitif warga, tidak melakukan pelacakan lokasi pengguna, hanya menyimpan cuplikan ringkas lowongan dari sumber tanpa menyimpan isi penuh artikel, serta tidak mencakup transaksi pembayaran daring.

2. **Lingkungan Komputasi dan Infrastruktur.** Pengembangan dilakukan secara lokal menggunakan Laragon yang menyediakan PHP 8, Apache, dan MySQL, sedangkan penerapan (*deployment*) menggunakan layanan komputasi awan berbasis *free tier*, yaitu frontend pada Vercel dan backend pada VPS, Railway, atau Render. Konfigurasi lingkungan dipisahkan antara pengembangan dan produksi agar kredensial serta konfigurasi basis data tidak terekspos. Penelitian ini tidak melakukan uji beban komputasi (*stress* dan *load testing*) untuk konkurensi skala besar, tidak melakukan redundansi basis data terdistribusi, serta tidak menguji perilaku sistem pada kondisi jaringan tidak stabil.

3. **Stack Teknologi.** Backend menggunakan framework Laravel dengan Object Relational Mapping Eloquent dan autentikasi berbasis token melalui Laravel Sanctum, dengan MySQL sebagai basis data relasional yang menggunakan karakter set utf8mb4. Frontend menggunakan Next.js dengan TypeScript untuk menjamin keamanan tipe data, dan Tailwind CSS untuk konsistensi desain antarmuka. Pemisahan arsitektur dua aplikasi ini dipilih agar setiap lapisan memiliki tanggung jawab yang jelas dan dapat dikembangkan secara independen. Pengelolaan versi kode sumber menggunakan Git.

4. **Fungsionalitas.** Fitur yang dikembangkan pada purwarupa ini meliputi empat modul utama, yaitu Beranda sebagai pintu masuk yang menyajikan ringkasan informasi terkini, Jelajah sebagai katalog terpadu untuk kuliner, fasilitas olahraga, dan layanan kesehatan, Berita sebagai warta kota dan informasi publik yang dilengkapi ringkasan otomatis bertanda “Diringkas AI” beserta atribusi dan tautan sumber, serta Lowongan Kerja dan Magang sebagai layanan informasi karier dengan alur kurasi dua tahap, yaitu Ambil dari Sumber, Curasi Administrator, hingga Tayang atau Ditolak, dilengkapi penyaringan wilayah, penandaan jenis lowongan, serta kedaluwarsa otomatis. Modul agenda Event dan Komunitas serta UMKM Lokal berada di luar lingkup penerapan purwarupa ini dan diposisikan sebagai pengembangan lanjutan. Di luar lingkup penerapan juga tercakup penyediaan aplikasi mobile native, transaksi pembayaran daring, notifikasi waktu nyata (*real-time*), dan integrasi API instansi pemerintah.

5. **Fokus Pengujian.** Evaluasi sistem berfokus pada pengujian fungsional backend melalui unit test dan feature test untuk memvalidasi logika bisnis serta endpoint REST API, pengujian lint dan build produksi pada frontend untuk memastikan konsistensi kode, serta pengujian alur utama (*end-to-end*) kurasi lowongan untuk memverifikasi rangkaian proses dari pengambilan data dari sumber hingga lowongan tayang pada halaman publik. Penelitian ini tidak mencakup uji penetrasi keamanan siber, uji beban pada konkurensi tinggi, maupun kuesioner psikometrik pengguna.

## 1.5 State of the Art

**Tabel 1.1 State of The Art**

### 1. Integrated Information System Modeling for Municipalities in Indonesia Toward Smart Cities

| | |
|---|---|
| **Peneliti** | A. Bahari, S. Ali, A. Djunid, A. A. Amsal, R. Novandri, I. Multama |
| **Lokasi** | Kota Pariaman, Sumatra Barat, Indonesia |
| **Tahun** | 2024 |
| **Nama Jurnal** | Evergreen — Joint Journal of Novel Carbon Resource Sciences & Green Asia Strategy, 11(4), 3307–3315 |
| **DOI/URL** | https://doi.org/10.5109/7326965 |
| **Hasil Penelitian** | Penelitian mengidentifikasi kategori *smart city* yang paling diprioritaskan bagi pemerintah kota di Indonesia (Data Centers & Analytics, Government Planning & Policy Design, dan Education Facilities), memetakan kebutuhan informasi pemerintah daerah, serta merumuskan model sistem informasi terintegrasi yang disesuaikan dengan konteks kota di Indonesia. |
| **Alasan Menjadi Tinjauan Penelitian** | Relevan karena sama-sama mengusulkan sistem informasi terintegrasi untuk pemerintah kota di Indonesia. Celah (*gap*) penelitian ini terletak pada orientasi yang masih berpusat pada sisi pemerintah (tata kelola dan pusat data) dan belum menyentuh platform yang menghubungkan warga secara langsung dengan informasi, tempat, serta tawaran karier lokal. Celah inilah yang diisi oleh TANGSEL LIA sebagai aplikasi berorientasi warga. |

### 2. Digital Transformation of Public Services: Evaluation of the Jakarta Kini (JAKI) Application in Jakarta’s Special Capital Region

| | |
|---|---|
| **Peneliti** | E. Ramadhan, E. Saparudi, G. P. N. Hakim |
| **Lokasi** | DKI Jakarta, Indonesia |
| **Tahun** | 2025 |
| **Nama Jurnal** | MONAS: Jurnal Inovasi Aparatur, 7(2), 92–104 |
| **DOI/URL** | https://doi.org/10.54849/monas.v7i2.289 |
| **Hasil Penelitian** | Penelitian mengevaluasi JAKI sebagai model *smart governance* yang mengintegrasikan berbagai layanan publik ke dalam satu platform digital. Hasilnya menunjukkan JAKI berperan memperluas akses layanan warga dan memperkuat tata kelola partisipatif, meskipun masih terkendala ketimpangan literasi digital, keterbatasan infrastruktur, dan isu perlindungan data. |
| **Alasan Menjadi Tinjauan Penelitian** | Menjadi rujukan utama atas efektivitas pendekatan aplikasi tunggal (*super app*) seperti yang hendak dibangun. Celah (*gap*) penelitian ini adalah JAKI berorientasi pada layanan administratif birokrasi pemerintah provinsi dan bergantung pada tingkat literasi digital pengguna. TANGSEL LIA memilih fokus yang berbeda, yaitu kurasi informasi lokal, direktori tempat, dan layanan informasi karier pada skala kota, dengan antarmuka yang sederhana dan ringan. |

### 3. Search Design and Online Job Search — New Avenues for Applied and Experimental Research

| | |
|---|---|
| **Peneliti** | P. A. T. Kircher |
| **Lokasi** | Jena, Jerman |
| **Tahun** | 2020 |
| **Nama Jurnal** | Labour Economics, 64, 101820 |
| **DOI/URL** | https://doi.org/10.1016/j.labeco.2020.101820 |
| **Hasil Penelitian** | Penelitian merumuskan desain pencarian lowongan kerja daring sebagai objek kajian tersendiri. Hasilnya menunjukkan bahwa proses penyaringan lowongan serta karakteristik antarmuka pencarian menentukan apakah pengguna menemukan lowongan yang relevan, dan bahwa pendekatan eksperimental diperlukan untuk menguji perilaku peserta pasar kerja daring. |
| **Alasan Menjadi Tinjauan Penelitian** | Menjadi landasan teoretis bahwa pencarian lowongan merupakan masalah desain informasi, bukan sekadar proses pencocokan profil dengan tawaran. Celah (*gap*) penelitian ini adalah fokusnya pada perilaku pasar kerja daring secara umum dan belum menyentuh konteks lowongan pada tingkat kota di Indonesia. TANGSEL LIA menerapkan prinsip tersebut lewat penyaringan wilayah, penandaan jenis lowongan, dan kurasi administrator yang membuat tawaran yang relevan mudah ditemukan. |

### 4. Sistem Informasi Pencarian Lowongan Kerja di Area Ciayumajakuning Berbasis Website

| | |
|---|---|
| **Peneliti** | D. Novianti, F. Wicaksono, M. R. Soemarsono |
| **Lokasi** | Kabupaten Cianjur, Jawa Barat, Indonesia |
| **Tahun** | 2024 |
| **Nama Jurnal** | INTI TALAFA, 15(2), 1–9 |
| **DOI/URL** | https://doi.org/10.32534/int.v15i2.6040 |
| **Hasil Penelitian** | Penelitian membangun sistem informasi pencarian lowongan kerja berbasis website untuk Area Ciayumajakuning. Hasilnya menunjukkan bahwa penghimpunan lowongan dari job board nasional ke dalam satu portal memerlukan penyaringan berdasarkan wilayah secara eksplisit, karena tawaran dari luar area tetap muncul pada hasil pencarian dan harus disaring secara manual. |
| **Alasan Menjadi Tinjauan Penelitian** | Menjadi rujukan utama atas upaya penyediaan informasi lowongan pada tingkat wilayah melalui basis website. Celah (*gap*) penelitian ini adalah cakupannya masih pada satu area dengan data yang dikurasi manual, belum berbasis agregasi otomatis dari *feed* RSS maupun memiliki alur status terstruktur. TANGSEL LIA menutup celah tersebut dengan agregasi *feed* RSS, penyaringan wilayah otomatis, dan alur kurasi dua tahap. |

### 5. Abstractive Text Summarization to Generate Indonesian News Highlight Using Transformers Model

| | |
|---|---|
| **Peneliti** | I. G. A. I. U. Putri, I. N. P. Trisna, N. K. D. Rusjayanthi |
| **Lokasi** | Universitas Udayana, Indonesia |
| **Tahun** | 2025 |
| **Nama Jurnal** | Journal of Information Systems and Informatics, 7(2), 1248–1263 |
| **DOI/URL** | https://doi.org/10.51519/journalisi.v7i2.1082 |
| **Hasil Penelitian** | Penelitian membandingkan model berbasis *transformer* (IndoT5 dan mBART) untuk menghasilkan ringkasan abstraktif berita berbahasa Indonesia sebagai solusi atas *information overload*. Hasilnya menunjukkan IndoT5 unggul pada metrik ROUGE dan BERT-Score dalam menghasilkan ringkasan berita yang lengkap dan relevan. |
| **Alasan Menjadi Tinjauan Penelitian** | Relevan dengan fitur ringkasan berita bertanda “Diringkas AI” pada modul Berita. Celah (*gap*) penelitian ini terletak pada fokus yang murni pada kualitas model pemrosesan bahasa alami dan belum terintegrasi ke dalam platform agregasi berita multi-sumber dengan atribusi serta tautan sumber. TANGSEL LIA menghadirkan fungsi serupa sebagai fitur produk, lengkap dengan penandaan sumber dan tautan aslinya. |

### 6. Using OpenStreetMap Point-of-Interest Data to Model Urban Change — A Feasibility Study

| | |
|---|---|
| **Peneliti** | L. Zhang, D. Pfoser |
| **Lokasi** | George Mason University, Amerika Serikat |
| **Tahun** | 2019 |
| **Nama Jurnal** | PLoS ONE, 14(2), e0212606 |
| **DOI/URL** | https://doi.org/10.1371/journal.pone.0212606 |
| **Hasil Penelitian** | Penelitian mengevaluasi kelayakan pemanfaatan data *point-of-interest* (POI) dari OpenStreetMap sebagai data geografis sukarela untuk memodelkan perubahan perkotaan, termasuk mengukur cakupan dan akurasi data POI tersebut. |
| **Alasan Menjadi Tinjauan Penelitian** | Menjadi dasar justifikasi pemanfaatan data OpenStreetMap yang terbuka dan legal sebagai sumber awal direktori tempat pada TANGSEL LIA. Celah (*gap*) penelitian ini adalah orientasinya pada analisis kualitas data spasial untuk perencanaan kota, dan belum memanfaatkan POI OSM sebagai basis direktori layanan warga yang dikonsumsi langsung oleh aplikasi. |

## 1.6 Sistematika Penulisan

Untuk memberikan gambaran yang jelas dan terstruktur mengenai penelitian ini, penyusunan Tugas Akhir dibagi menjadi lima bab dengan sistematika penulisan sebagai berikut:

**BAB 1 PENDAHULUAN**

Bab ini menguraikan dasar pemikiran penelitian yang mencakup latar belakang masalah, rumusan masalah, tujuan penelitian, batasan masalah (ruang lingkup), tinjauan studi literatur (*State of The Art*), serta sistematika penulisan yang digunakan sebagai panduan penyusunan laporan.

**BAB 2 LANDASAN TEORI**

Bab ini memaparkan landasan teori yang mendukung penelitian, meliputi konsep aplikasi web dan REST API, *framework* Laravel dan ORM Eloquent, *framework* Next.js dan React, autentikasi API berbasis token dengan Laravel Sanctum, model relasional dan normalisasi basis data, agregasi konten berbasis *feed* RSS serta deduplikasi, penyaringan informasi berdasarkan wilayah, dan pengujian perangkat lunak. Selain itu, diuraikan pula terminologi teknis dan perangkat lunak pendukung yang digunakan dalam pengembangan sistem.

**BAB 3 METODOLOGI PENELITIAN**

Bab ini menjelaskan tahapan sistematis penelitian yang mengadaptasi pendekatan pengembangan iteratif dan inkremental. Tahapan tersebut mencakup analisis kebutuhan dan desain arsitektur, pengembangan backend Laravel REST API, pengembangan frontend Next.js, integrasi fitur penunjang, pengujian dan penjaminan kualitas, hingga penerapan (*deployment*) dan pemeliharaan sistem.

**BAB 4 HASIL DAN PEMBAHASAN**

Bab ini memaparkan realisasi rancangan metodologi ke dalam bentuk kode program, mencakup implementasi antarmuka pengguna (Beranda, Jelajah, Berita, Lowongan Kerja dan Magang, serta dasbor administrator), integrasi REST API backend Laravel dengan frontend Next.js, pengambilan data lowongan dari *feed* RSS beserta deduplikasi dan penyaringan wilayah, serta alur kurasi dan kedaluwarsa lowongan. Bab ini juga menyajikan hasil pengujian fungsional, pengujian alur utama (*end-to-end*), pemeriksaan lint, dan build produksi sebagai bentuk pembuktian kelayakan teknis.

**BAB 5 KESIMPULAN DAN SARAN**

Bab ini berisi kesimpulan akhir yang ditarik dari hasil analisis dan pengujian untuk menjawab seluruh rumusan masalah. Selain itu, bab ini juga memberikan saran teknis maupun operasional guna mendukung pengembangan dan kelayakan skalabilitas sistem di masa mendatang.

## Daftar Pustaka

[1] A. Caragliu, C. Del Bo, and P. Nijkamp, “Smart Cities in Europe,” *Journal of Urban Technology*, vol. 18, no. 2, pp. 65–82, 2011, doi: 10.1080/10630732.2011.601117.

[2] A. Bahari, S. Ali, A. Djunid, A. A. Amsal, R. Novandri, and I. Multama, “Integrated Information System Modeling for Municipalities in Indonesia Toward Smart Cities,” *Evergreen — Joint Journal of Novel Carbon Resource Sciences & Green Asia Strategy*, vol. 11, no. 4, pp. 3307–3315, 2024, doi: 10.5109/7326965.

[3] E. Ramadhan, E. Saparudi, and G. P. N. Hakim, “Digital Transformation of Public Services: Evaluation of the Jakarta Kini (JAKI) Application in Jakarta’s Special Capital Region,” *MONAS: Jurnal Inovasi Aparatur*, vol. 7, no. 2, pp. 92–104, 2025, doi: 10.54849/monas.v7i2.289.

[4] P. A. T. Kircher, “Search design and online job search — new avenues for applied and experimental research,” *Labour Economics*, vol. 64, p. 101820, 2020, doi: 10.1016/j.labeco.2020.101820.

[5] D. Novianti, F. Wicaksono, and M. R. Soemarsono, “Sistem Informasi Pencarian Lowongan Kerja di Area Ciayumajakuning Berbasis Website,” *INTI TALAFA*, vol. 15, no. 2, pp. 1–9, 2024, doi: 10.32534/int.v15i2.6040.

[6] I. G. A. I. U. Putri, I. N. P. Trisna, and N. K. D. Rusjayanthi, “Abstractive Text Summarization to Generate Indonesian News Highlight Using Transformers Model,” *Journal of Information Systems and Informatics*, vol. 7, no. 2, pp. 1248–1263, 2025, doi: 10.51519/journalisi.v7i2.1082.

[7] M. Haklay and P. Weber, “OpenStreetMap: User-Generated Street Maps,” *IEEE Pervasive Computing*, vol. 7, no. 4, pp. 12–18, 2008, doi: 10.1109/MPRV.2008.80.

[8] L. Zhang and D. Pfoser, “Using OpenStreetMap point-of-interest data to model urban change — A feasibility study,” *PLoS ONE*, vol. 14, no. 2, p. e0212606, 2019, doi: 10.1371/journal.pone.0212606.
