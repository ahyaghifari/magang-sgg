# Brief: Dokumentasi Sistem — Portal Internship Syifa Global Group

> File ini adalah **bahan presentasi/dokumentasi**, ditulis dengan bahasa yang mudah dipahami orang non-teknis (atasan, pembimbing, atau audiens presentasi). Istilah teknis (nama file, class, route) sengaja diminimalkan di badan teks — kalau perlu contoh lebih rinci untuk keperluan teknis, bisa ditambahkan sebagai lampiran terpisah. Semua fakta di sini diambil langsung dari kode per 2026-09-26.

---

## 1. Instruksi untuk ChatGPT (kalau file ini ditempel ke alat lain untuk dirapikan)

Susun **Dokumentasi Sistem** dari aplikasi berikut untuk keperluan presentasi/laporan internal. Gunakan bahasa Indonesia formal namun tetap mengalir dan mudah dipahami — hindari istilah teknis kecuali benar-benar perlu. Struktur dokumen yang diinginkan:

1. Pendahuluan (latar belakang, tujuan sistem)
2. Gambaran Umum Sistem
3. Arsitektur & Teknologi (ringkas, tidak perlu detail implementasi)
4. Peran Pengguna & Hak Akses
5. Modul & Fitur (per modul, jelaskan alur kerja dari sudut pandang pengguna), termasuk penilaian akhir & sertifikat magang
6. Struktur Data / Entitas Utama (tabel deskriptif, bukan skema SQL)
7. Alur Autentikasi (login, SSO, persetujuan akun)
8. Integrasi Eksternal
9. Penutup / Rencana Pengembangan Selanjutnya (opsional)

Buat naratif, bukan sekadar daftar — jelaskan **mengapa** fitur itu ada dan **bagaimana alurnya** dari sudut pandang pengguna. Boleh tambahkan diagram alur sederhana untuk proses persetujuan akun dan alur presensi.

---

## 2. Gambaran Umum

**Nama sistem:** Portal Internship Syifa Global Group. Nama ini yang tampil di judul halaman, halaman login, dan sidebar aplikasi. (Secara teknis nama kerja/kode proyek di belakang layar masih memakai kata "magang" — hal ini tidak terlihat oleh pengguna dan tidak berpengaruh ke tampilan.)

**Tujuan sistem:** Portal digital untuk mengelola seluruh siklus peserta magang (intern) di perusahaan — mulai dari pendaftaran, penempatan ke unit kerja, pencatatan jurnal harian, presensi, pengajuan izin, pemberian tugas, sampai penilaian oleh pembimbing. Tujuannya menggantikan proses yang tadinya manual/tersebar (WhatsApp, kertas, spreadsheet) dengan satu sistem terpusat.

**Jenis aplikasi:** Aplikasi web internal perusahaan (bukan untuk publik), dipakai oleh dua kelompok pengguna utama: pembimbing/pengawas magang dan peserta magang itu sendiri, plus admin yang mengelola data di belakang layar.

---

## 3. Teknologi

Ringkasan teknologi yang dipakai, untuk memberi gambaran skala dan kematangan sistem:

| Bagian | Teknologi | Keterangan singkat |
|---|---|---|
| Backend | Laravel (PHP) | Framework backend yang umum dipakai untuk aplikasi web perusahaan, dikenal stabil dan banyak dukungan komunitas. |
| Tampilan interaktif | Livewire | Halaman terasa interaktif (tanpa reload penuh) tanpa perlu membangun aplikasi front-end terpisah. |
| Panel admin | Filament | Panel khusus untuk admin mengelola data master (pengguna, institusi, perusahaan, dll.), dengan tampilan yang sudah disesuaikan warna & gaya perusahaan. |
| Basis data | MySQL/relasional | Data tersimpan terstruktur dan saling terhubung (mis. satu intern terhubung ke satu institusi, satu unit kerja, dst). Ada pula sambungan **baca-saja** ke sistem absensi (HRIS) perusahaan untuk mengambil data mentah alat sidik jari. |
| Dokumen PDF | DomPDF | Membuat sertifikat magang & form penilaian resmi langsung dari sistem dalam bentuk PDF siap cetak. |
| Login | Login email/password, plus opsional **Single Sign-On (SSO)** lewat Keycloak | Karyawan yang sudah punya akun terpusat perusahaan bisa langsung login tanpa akun terpisah. |
| Desain | Mobile-first, mendukung mode gelap di sisi portal | Portal dirancang supaya nyaman dipakai dari HP, karena peserta magang & pembimbing sering mengaksesnya di lapangan. |
| Notifikasi | Web Push (standar notifikasi browser) | Pemberitahuan muncul di panel notifikasi HP/komputer seperti aplikasi chat — tanpa perlu memasang aplikasi dari Play Store/App Store. Portal juga bisa "dipasang" ke layar utama HP layaknya aplikasi. |

---

## 4. Peran Pengguna & Hak Akses

Setiap akun di sistem punya satu dari lima peran berikut:

| Peran | Siapa | Bisa apa saja |
|---|---|---|
| **Admin** | Pengelola sistem (biasanya staf HR/IT) | Akses penuh ke panel admin: mengelola akun, data peserta magang, institusi, perusahaan/unit, jadwal kerja, sampai menyetujui pendaftar baru. Admin tidak pernah terblokir status persetujuan apa pun — selalu bisa masuk. |
| **Pembimbing** | Pengawas magang yang ditugaskan langsung ke peserta tertentu | Hanya melihat peserta magang yang **ditugaskan kepadanya**. Untuk peserta tersebut: menilai jurnal, memberi & mengelola tugas, **menyetujui atau menolak pengajuan izin**, memantau presensi, serta mengisi penilaian akhir & sertifikat. |
| **Mentor** | Pendamping peserta magang, terpisah dari Pembimbing | Bisa **melihat seluruh** peserta magang (jurnal, tugas, presensi, sertifikat) dan **ikut berdiskusi/berkomentar** di jurnal maupun tugas peserta mana pun, supaya antar-tim tetap transparan. Namun hanya bisa **mengelola** (menilai jurnal, mengedit/menghapus tugas, mengisi penilaian akhir) peserta yang dimentorinya sendiri. Mentor **tidak** ikut memutuskan pengajuan izin — itu wewenang Pembimbing. |
| **Pimpinan** | Direktur/pimpinan perusahaan yang ingin memantau tanpa ikut mengelola | Punya dashboard ringkasan tersendiri berisi daftar seluruh peserta magang, jurnal, presensi, dan pengajuan izin — sifatnya **hanya memantau**, tidak bisa menyetujui/menolak izin, menilai jurnal, maupun mengelola tugas. |
| **Intern** (peserta magang) | Peserta magang aktif | Pengguna utama sehari-hari: mengisi jurnal kegiatan harian, mencatat presensi, mengajukan izin/sakit, serta melihat, menyelesaikan, atau menunda (dengan alasan) tugas dari pembimbing/mentor. |

Setiap peserta magang bisa punya **satu Pembimbing dan satu Mentor** sekaligus (boleh dua orang berbeda). Admin yang menentukan pasangan ini di panel admin.

Catatan: beberapa admin, pembimbing, atau mentor (mis. seorang Direktur yang juga aktif mengisi jurnal) bisa punya data peserta magang sendiri — ada saklar "Lihat sebagai Intern" untuk berpindah sudut pandang tanpa perlu dua akun terpisah.

---

## 5. Modul & Fitur

### 5.1 Pendaftaran & Persetujuan Akun
Calon peserta mendaftar sendiri lewat halaman pendaftaran (nama, email, asal institusi/sekolah, jenis kelamin, kata sandi). Akun baru **belum aktif** — tidak bisa login sampai disetujui oleh admin di panel admin (admin bisa menyetujui atau menolak satu per satu, atau sekaligus banyak akun). Setelah disetujui, admin melengkapi data penempatannya secara manual: unit kerja, Pembimbing dan Mentor yang mendampingi, serta periode magang (tanggal mulai & selesai).

### 5.2 Login & Autentikasi
Login pakai email dan kata sandi seperti biasa. Sebagai alternatif, tersedia **Single Sign-On (SSO)** lewat Keycloak — pengguna yang sudah punya akun terpusat perusahaan bisa langsung masuk tanpa mendaftar ulang, dicocokkan berdasarkan alamat email. Kalau pengguna login lewat SSO lalu logout dari portal, sesi di sistem SSO ikut berakhir juga (logout menyeluruh, bukan cuma dari portal ini).

### 5.3 Jurnal Harian
Peserta magang mengisi catatan kegiatan setiap hari, boleh dilengkapi lampiran foto atau dokumen PDF (kolom teks kegiatan boleh dikosongkan kalau lampiran sudah cukup menjelaskan). Foto yang diunggah otomatis dikecilkan ukurannya di perangkat pengguna sebelum dikirim (supaya hemat kuota & penyimpanan), dan ada tombol untuk langsung memotret dari kamera HP. Pembimbing/Mentor melihat jurnal peserta dalam satu tampilan (halaman "Kegiatan"). Secara bawaan halaman ini menampilkan **hari ini dan satu minggu sebelumnya**, dengan **hari ini selalu di paling atas** sebagai fokus utama — kalau hari ini belum ada jurnal masuk, bagian "Hari Ini" tetap tampil dengan keterangan kosong. Jurnal dikelompokkan per hari dengan judul nama hari saja ("Hari Ini", "Selasa", "Rabu", …) yang masing-masing punya **warna berbeda** supaya mudah dibedakan sekilas. Tampilan bisa difilter per tanggal, peserta, atau kata kunci. Pembimbing/Mentor lalu bisa memberi **penilaian bintang 1–5** pada jurnal peserta binaannya sendiri sebagai bentuk evaluasi kinerja — nilai rata-rata dari semua pembimbing yang menilai ditampilkan kembali ke peserta. Foto lampiran bisa diperbesar (zoom) langsung di halaman yang sama tanpa membuka tab baru.

### 5.4 Presensi
Data kehadiran berasal dari dua sumber: presensi manual dari portal, dan sinkronisasi otomatis dari alat sidik jari perusahaan. Sistem membaca data mentah dari alat tersebut, mencocokkan ke identitas peserta magang, lalu menghitung jam masuk/pulang, keterlambatan, dan pulang cepat berdasarkan jadwal kerja yang berlaku di masing-masing perusahaan. Pembimbing bisa memantau presensi seluruh peserta binaannya dari satu halaman. Halaman yang sama juga menampilkan daftar **izin/sakit yang sudah disetujui**, terpisah dari rekap sidik jari, supaya hari tidak masuk karena izin tidak disangka alfa. (Catatan: sistem ini sengaja disederhanakan untuk jadwal kerja tetap — belum mendukung shift fleksibel atau lembur lintas hari.)

### 5.5 Pengajuan Izin
Peserta magang mengajukan izin atau sakit lewat portal, lalu Pembimbing-nya (atau admin) meninjau untuk menyetujui atau menolak. Izin bisa untuk **sehari penuh** atau hanya **beberapa jam** (mis. pulang cepat, ada urusan 2 jam) dengan mengisi jam mulai & jam selesai.

- **Notifikasi otomatis**: begitu ada pengajuan baru, Pembimbing mendapat notifikasi langsung di HP/komputernya (lengkap dengan tanggal izin dan alasannya) — dan sebaliknya, peserta magang diberi tahu begitu izinnya diputuskan. Selengkapnya soal notifikasi di bagian 5.15.
- Sebagai cadangan kalau notifikasi tidak aktif, menu "Izin Intern" di sisi pembimbing selalu menampilkan angka jumlah pengajuan yang masih menunggu, jadi tidak akan terlewat.
- Pengajuan yang sudah diputuskan (disetujui/ditolak) bisa dihapus oleh pembimbing untuk merapikan data lama; pengajuan yang masih menunggu tidak bisa dihapus sampai diputuskan dulu. Setiap penghapusan (izin, tugas, komentar) selalu meminta konfirmasi dulu supaya tidak terhapus karena salah pencet.
- *Pengembangan lanjutan yang masih dipertimbangkan:* mengirim notifikasi lewat email atau WhatsApp — belum dikerjakan karena butuh pengaturan tambahan (server pengirim email yang sesungguhnya, atau berlangganan layanan pihak ketiga untuk WhatsApp).

### 5.6 Pemberian & Pengelolaan Tugas
Pembimbing bisa memberi tugas langsung ke peserta magang lewat portal (lengkap dengan tenggat waktu — tanggal dan jam), atau peserta magang mencatat sendiri tugas yang disampaikan secara lisan. Saat tugas selesai, peserta mengunggah **satu atau beberapa foto** sebagai bukti pengerjaan (foto-foto ini bisa langsung dijadikan catatan jurnal harian juga, sekali klik).

- **Satu tugas untuk banyak peserta sekaligus**: kalau tugasnya sama, pembimbing/mentor cukup mengisi form sekali lalu memilih beberapa peserta. Pilihan peserta dibagi dua kotak terpisah — **"Peserta yang Kamu Bimbing/Mentori"** dan **"Peserta Lain (Lintas Pembimbing)"** — masing-masing dengan pilihan dropdown sendiri; peserta yang sudah dipilih tampil sebagai label yang bisa dibatalkan dengan sekali klik, dan ada tombol "Pilih semua" untuk seluruh binaan sendiri. Walau dikirim sekaligus, **setiap peserta tetap mendapat tugasnya masing-masing**, sehingga status selesai/ditunda, foto bukti, dan diskusinya tidak tercampur antar-peserta.
- **Tugas lintas pembimbing**: Pembimbing/Mentor boleh memberi tugas ke peserta magang mana pun, tidak hanya binaannya sendiri, misalnya saat butuh bantuan peserta dari unit lain. Kalau ada peserta lintas pembimbing yang dipilih, form menampilkan siapa Pembimbing & Mentor asli peserta tersebut supaya tetap jelas. Pemberi tugas tetap bisa mengelola tugas buatannya sendiri.
- Peserta magang langsung mendapat **notifikasi di HP** begitu diberi tugas, lengkap dengan nama pemberi tugas dan tenggatnya (lihat 5.15).
- **Kalau peserta belum bisa mengerjakan** (ada urusan lain, dsb.), tersedia tombol **Tunda** dengan alasan singkat. Bahasanya sengaja dibuat sopan — form mengajak peserta menyampaikan alasan dengan santun, misalnya *"Mohon maaf kak, tugas ini saya tunda dulu karena… Apakah boleh saya kerjakan besok?"*. Pemberi tugas langsung mendapat notifikasi beserta alasannya, lalu bisa menyesuaikan tugas tersebut (ubah tenggat/keterangan) atau membukanya kembali. Tugas seperti ini diberi label **"Ditunda"** berwarna oranye (bukan merah) supaya tidak terkesan sebagai penolakan.
- Peserta magang hanya bisa mengubah status tugasnya sendiri (mulai, tandai selesai, atau tunda) — tidak bisa mengedit maupun menghapus tugas, supaya kontrol isi tugas tetap di tangan pembimbing.
- Pembimbing/Mentor punya kendali penuh atas tugas peserta binaannya (dan tugas yang ia berikan sendiri): bisa menandai selesai, membuka kembali, mengedit, atau menghapus tugas kapan saja.
- Sama seperti izin, menu "Tugas Intern" di sisi pembimbing menampilkan angka peringatan kalau ada tugas yang ditunda dan belum ditindaklanjuti.

### 5.7 Diskusi/Komentar
Setiap jurnal atau tugas punya kolom diskusi sendiri — pembimbing dan peserta bisa saling berkomentar langsung di situ, jadi tidak perlu pindah ke aplikasi chat terpisah untuk membahas satu kegiatan/tugas tertentu. Setiap komentar menampilkan foto profil penulisnya (atau inisial nama kalau belum ada foto). Komentar hanya bisa dihapus oleh penulisnya sendiri.

- **Mentor bisa berkomentar ke semua peserta**, bukan hanya mentee-nya sendiri — baik di jurnal maupun di tugas. Ini sejalan dengan prinsip Mentor boleh melihat semua peserta; yang tetap dibatasi hanyalah aksi mengelola (menilai bintang, mengedit/menghapus tugas).
- **Ada notifikasi untuk setiap komentar baru.** Yang diberi tahu adalah semua pihak di diskusi tersebut — peserta pemilik jurnal/tugas, Pembimbing & Mentor-nya, pemberi tugas, dan siapa pun yang pernah ikut berkomentar — kecuali penulis komentarnya sendiri. Komentar beruntun di satu diskusi digabung menjadi satu notifikasi yang diperbarui, supaya panel notifikasi HP tidak penuh oleh satu obrolan.

### 5.8 Struktur Organisasi
Data perusahaan disusun berjenjang: satu **Perusahaan** punya beberapa **Unit kerja** (mis. IT, Humas), dan setiap peserta magang atau pegawai ditempatkan di salah satu unit tersebut. Ini terpisah dari data **Institusi asal** (sekolah/kampus peserta magang) — dua hal yang berbeda: satu soal dari mana peserta berasal, satu lagi soal di mana dia ditempatkan bekerja. Admin yang mengatur struktur ini dan menentukan penempatan setiap peserta secara manual.

Khusus akun Pembimbing/Mentor/Pimpinan, admin juga bisa mengaitkan akun tersebut langsung ke satu **Perusahaan** (terpisah dari Unit) — berguna kalau pembimbing/mentor/pimpinan tersebut mengawasi lintas beberapa unit dalam satu perusahaan yang sama, supaya tetap jelas perusahaan mana yang menjadi induknya. Kolom ini sifatnya **hanya info profil**, bukan pembatas akses data — tidak mengubah data peserta magang mana saja yang bisa dilihat/dikelola oleh akun tersebut.

### 5.9 Panel Admin
Tempat admin mengelola seluruh data master sistem: akun pengguna, data peserta magang, institusi asal, data perusahaan & unit kerja, jurnal, presensi, dan jadwal kerja.

- **Tampilan** panel admin sudah disesuaikan supaya senada dengan portal peserta (warna, bentuk kartu, tabel) — bukan lagi tampilan bawaan generik.
- **Ringkasan di halaman utama**: kartu statistik jumlah peserta magang aktif, jurnal yang masuk hari ini, ringkasan presensi hari ini, dan jumlah akun yang menunggu persetujuan — masing-masing bisa diklik untuk langsung menuju datanya. Ditambah grafik tren jumlah jurnal 7 hari terakhir.
- Data peserta magang punya kolom **Nama Panggilan** (opsional), untuk membantu pembimbing mengenali peserta dengan nama yang lebih akrab. Nama ini sudah tampil di halaman Intern dan Sertifikat di sisi pembimbing.
- Saat admin membuka detail sebuah jurnal, foto lampiran kegiatan bisa diklik untuk dibuka dalam ukuran penuh di tab baru.
- **Detail Unit kerja**: dari daftar Unit, admin bisa membuka halaman detail setiap unit yang menampilkan **siapa saja peserta magang** di unit itu (lengkap dengan institusi, pembimbing, mentor, periode, dan status Berjalan/Selesai) serta **siapa saja pegawainya** (pembimbing, mentor, pimpinan, admin — dengan filter per peran). Kedua daftar ini bisa dicari, dan satu klik membuka data orang tersebut. Kolom jumlah pegawai di daftar Unit juga tidak lagi ikut menghitung akun peserta magang.
- Di data peserta magang, admin juga mengatur **Pembimbing**, **Mentor**, dan **periode magang** (tanggal mulai & selesai), serta bisa mengisi atau mengoreksi **penilaian akhir** (lihat 5.12).

### 5.10 Dashboard Pimpinan
Khusus akun berperan **Pimpinan**, sistem menyediakan satu halaman ringkasan tersendiri (bukan halaman "Beranda" biasa) yang menampilkan data **seluruh** peserta magang sekaligus: **daftar Seluruh Intern** (kartu per peserta yang sama seperti halaman Intern milik Mentor — foto, asal sekolah, unit, periode, rekap jurnal/tugas/presensi/izin, dan jurnal/tugas lengkapnya bisa dibuka dalam satu jendela), jurnal, presensi (default: hari ini), dan pengajuan izin — masing-masing bisa dicari/difilter dan dijelajahi terpisah. Semuanya ada di dashboard ini, tanpa menu tambahan. Halaman ini murni untuk memantau: tidak ada tombol menyetujui/menolak izin, menilai jurnal, atau mengelola tugas di sini — keputusan/pengelolaan tetap jadi wewenang Pembimbing/Mentor. Pimpinan juga sengaja tidak diikutsertakan dalam notifikasi pengajuan izin baru, karena bukan pihak yang memutuskan.

### 5.11 Daftar Intern (sisi Pembimbing/Mentor)
Halaman ringkasan berisi seluruh peserta magang yang bisa dilihat oleh pembimbing/mentor: asal sekolah/kampus, unit penempatan, periode magang, dan rekap singkat per peserta (jumlah jurnal, tugas selesai/total, presensi hadir/terlambat/absen, serta izin). Dari sini, jurnal dan tugas satu peserta bisa dibuka lengkap dalam satu jendela. Halaman ini hanya untuk melihat, tidak untuk mengubah data, dan bisa dicari berdasarkan nama peserta atau nama institusi.

### 5.12 Penilaian Akhir & Sertifikat Magang
Di akhir masa magang, Pembimbing/Mentor mengisi **penilaian akhir** mengikuti form resmi perusahaan *"Appraisal on the Job Training Result"*, yang terdiri dari 10 kriteria dalam 2 kelompok:

| Kelompok | Kriteria |
|---|---|
| **Attitude** (sikap) | Performance & nilai-nilai Syifa, Motivation, Responsibility, Cooperativeness, Attendance |
| **Knowledge & Skill** | Job Knowledge, Quality of Work, Job Speed, Initiative, Improvement Achieved |

- Setiap kriteria dinilai dengan klik **bintang 1–5** (boleh setengah bintang, mis. 4,5), sama seperti menilai jurnal. Penilai juga bisa menambahkan catatan bebas.
- **Nilai akhir** dihitung otomatis dari rata-rata 10 kriteria, lalu diberi predikat: **Excellent** (≥ 4,5), **Good** (≥ 3,5), **Fair** (≥ 2,5), **Below Average** (≥ 1,5), dan **Poor** (di bawahnya). Sistem juga mencatat siapa yang menilai dan kapan.
- Mentor bisa melihat sertifikat semua peserta, tetapi hanya bisa menilai peserta yang dimentorinya.
- Sistem langsung membuat **dokumen PDF yang bisa dicetak bolak-balik**. Halaman depan berisi **Sertifikat PKL** (logo perusahaan, nomor sertifikat, dan nama pembimbing), dan halaman belakang berisi **form penilaian resmi** lengkap dengan nilai dan predikatnya. Dokumen bisa dilihat di browser atau diunduh.
- Peserta magang bisa melihat & mengunduh sertifikatnya sendiri dari halaman Beranda **setelah tanggal selesai magangnya tiba**. Pembimbing/Mentor dan admin bisa mengaksesnya kapan saja untuk pratinjau atau cetak.

### 5.13 Profil & Personalisasi Beranda Peserta
Peserta magang bisa mengganti **foto profil** sendiri dan memilih **warna kartu Beranda** sesuai selera. Foto dipotong bulat langsung di HP sebelum dikirim, jadi foto aslinya tidak ikut diunggah. Warna teks di kartu otomatis menyesuaikan supaya tetap terbaca, baik di warna terang maupun gelap.

Foto profil ini juga muncul di samping nama peserta di halaman-halaman Pembimbing/Mentor (Kegiatan, Tugas, Presensi, Izin, Intern, dan Sertifikat), sehingga pembimbing lebih mudah mengenali wajah peserta binaannya. Foto bisa diklik untuk diperbesar. Kalau peserta belum mengunggah foto, yang tampil adalah inisial namanya. Foto yang sama juga tampil di kartu profil pada sidebar (bagian bawah menu), di kolom diskusi, dan sebagai gambar pada notifikasi HP yang dikirim oleh peserta tersebut — selalu dalam bentuk bulat penuh.

### 5.14 Navigasi & Pengalaman Pengguna di HP
Karena banyak dipakai lewat HP, menu utama (sidebar) dirancang sebagai laci yang bisa dibuka lewat tombol ataupun dengan **geser jari dari tepi kiri layar** — kebiasaan yang sudah familiar dari aplikasi-aplikasi populer. Menu "Beranda" (halaman ringkasan untuk peserta magang) ditempatkan sebagai menu pertama yang paling mudah dijangkau. Menu yang tampil menyesuaikan peran: Pembimbing/Mentor melihat Kegiatan, Tugas, Presensi, Izin (khusus Pembimbing), Intern, dan Sertifikat, sedangkan peserta magang melihat Beranda, Jurnal Harian, Tugas, dan Izin. Ikon tab browser (favicon) memakai lambang Syifa Global Group.

Portal juga bisa **dipasang ke layar utama HP** layaknya aplikasi (menu "Tambahkan ke layar utama" di Chrome, atau "Tambah ke Layar Utama" di Safari iPhone), dengan nama **Magang SGG** dan ikon lambang Syifa. Setelah dipasang, portal terbuka tanpa bilah alamat browser — dan khusus iPhone, cara inilah yang membuat notifikasi bisa diterima.

### 5.15 Notifikasi di HP
Supaya informasi penting tidak terlewat, portal mengirim notifikasi yang muncul di **panel notifikasi HP** (atau pojok layar komputer), sama seperti notifikasi WhatsApp — walaupun portal sedang tidak dibuka. Kejadian yang memicu notifikasi:

| Kejadian | Siapa yang diberi tahu | Isi notifikasi |
|---|---|---|
| 📋 Tugas baru diberikan | Peserta yang diberi tugas | Nama pemberi tugas, judul tugas, tenggat |
| ⏸️ Tugas ditunda peserta | Pemberi tugas | Nama peserta, judul tugas, alasan menunda |
| 💬 Komentar baru di diskusi | Semua pihak di diskusi itu (kecuali penulisnya) | Nama penulis, jurnal/tugas yang dibahas, isi komentar |
| 📝 / 🤒 Pengajuan izin/sakit baru | Pembimbing | Nama peserta, tanggal izin, alasan |
| ✅ / ❌ Izin diputuskan | Peserta pengaju | Hasil keputusan, siapa yang memutuskan, catatan |

- **Tampilan notifikasi** dibuat rapi dan mudah dibaca sekilas: judul diawali emoji sesuai jenisnya, isi dipecah beberapa baris berlabel, gambar di sisi kanan memakai **foto profil pengirim** (atau logo Syifa), serta tombol aksi seperti **Lihat Tugas / Balas / Tinjau** dan **Nanti**. Mengetuk notifikasi langsung membuka halaman yang relevan. (Warna dan huruf notifikasi mengikuti bawaan masing-masing HP — ini batasan standar notifikasi, bukan dari sistem.)
- **Berlaku untuk Android maupun iPhone.** Di Android cukup lewat Chrome. Di iPhone (iOS 16.4 ke atas), portal harus dipasang dulu ke layar utama lalu dibuka dari ikon tersebut — kalau pengguna iPhone menekan tombol aktifkan dari Safari biasa, sistem menampilkan petunjuk langkah-langkahnya.
- **Perlu diaktifkan sekali per perangkat** lewat tombol "Aktifkan Notifikasi" (di halaman Tugas), lalu memilih "Izinkan". Tombol ini otomatis tersembunyi kalau notifikasi di perangkat tersebut sudah aktif. Satu akun bisa menerima notifikasi di beberapa perangkat sekaligus, asalkan masing-masing sudah diaktifkan.
- **Tahan gangguan**: setiap kali portal dibuka, sistem otomatis memperbarui "alamat" notifikasi perangkat di server, sehingga notifikasi tetap sampai walaupun browser memperbarui datanya. Notifikasi dikirim dengan prioritas tinggi dan disimpan hingga 24 jam, jadi HP yang sedang mati atau tanpa sinyal tetap menerimanya begitu tersambung lagi.
- Hal di luar kendali sistem yang bisa membuat notifikasi terlambat/tidak berbunyi: mode Senyap/Jangan Ganggu, izin notifikasi Chrome dimatikan di pengaturan HP, atau penghemat baterai yang agresif (umum di beberapa merek HP Android).

---

## 6. Alur Persetujuan Akun (ringkas, untuk diagram)

```
Calon peserta mendaftar mandiri → status "menunggu" (belum bisa login)
        │
        ▼
   Admin meninjau di panel admin
        │
   ┌────┴────┐
   ▼         ▼
Disetujui   Ditolak (data dihapus)
   │
   ▼
Peserta bisa login → admin menentukan unit kerja, Pembimbing, Mentor & periode magang
        │
        ▼
Peserta aktif: mengisi jurnal, presensi, mengajukan izin, mengerjakan tugas
        │
        ▼
Pembimbing/Mentor memantau, menilai jurnal, memberi tugas & berdiskusi
(Pembimbing juga memutuskan izin) — semua pihak diberi tahu lewat notifikasi HP
        │
        ▼
Akhir magang: Pembimbing/Mentor mengisi penilaian 10 kriteria
        │
        ▼
Sertifikat PKL + form penilaian (PDF) bisa diunduh peserta
```

---

## 7. Integrasi dengan Sistem Lain

1. **Single Sign-On (Keycloak)** — opsional, memungkinkan login terpusat lintas aplikasi perusahaan berbasis email yang sama, tanpa perlu akun terpisah per aplikasi.
2. **Sistem Absensi Perusahaan (HRIS)** — sistem ini membaca data mentah alat sidik jari perusahaan secara berkala (hanya membaca, tidak mengubah data di sistem sumber), lalu mengolahnya jadi rekap kehadiran yang tampil di portal.

---

## 8. Catatan Tambahan (untuk internal, opsional dimasukkan ke dokumentasi)

- Sistem peran pengguna sempat punya peran keempat bernama **"User"** (peran generik/cadangan) — per 2026-09-16 peran ini **dihapus** karena tidak pernah benar-benar dipakai dalam alur bisnis.
- Per 2026-09-17, ditambahkan dua peran baru: **Mentor** dan **Pimpinan** (akses pemantauan ringkas lewat dashboard tersendiri, tanpa wewenang mengelola). Total peran saat ini: Admin, Pembimbing, Mentor, Pimpinan, Intern.
- Antara 2026-09-18 dan 2026-09-22, hak akses Pembimbing & Mentor dibedakan. Pembimbing hanya melihat peserta yang ditugaskan kepadanya. Mentor melihat semua peserta tetapi hanya mengelola mentee-nya sendiri. Keputusan izin hanya di tangan Pembimbing (dan admin).
- Rubrik penilaian akhir sempat beberapa kali berganti (nilai 0–100 → 4 aspek → 10 kriteria form resmi). Yang berlaku sekarang adalah 10 kriteria dengan skala 1–5 bintang. Teks bantuan di form penilaian panel admin masih menyebut batas predikat versi lama (≥3,50 Excellent, dst.) dan perlu diselaraskan dengan batas yang benar-benar dipakai sistem (≥4,5 Excellent, ≥3,5 Good, ≥2,5 Fair, ≥1,5 Below Average).
- Nama brand yang tampil ke pengguna diubah dari "Magang" menjadi **"Internship"** (judul halaman, login, sidebar) per 2026-09-16 — istilah "magang" di kalimat-kalimat deskriptif lain (mis. "peserta magang") sengaja tetap dipakai karena lebih wajar dalam Bahasa Indonesia.
- Sebagian sistem peran lama (dari paket pihak ketiga yang sebelumnya dipakai) masih berjalan berdampingan sementara proses migrasi ke sistem peran baru selesai sepenuhnya — tidak berdampak ke pengguna, murni urusan teknis di belakang layar.
- Nama aplikasi secara resmi (`APP_NAME`) di pengaturan server belum disesuaikan dari nilai bawaan — ini murni pengaturan teknis, tidak memengaruhi tampilan yang dilihat pengguna.
- Per 2026-09-26, istilah "menolak tugas" di sisi pengguna diganti menjadi **"menunda tugas"** (tombol, label status, dan notifikasi) supaya lebih sopan. Di balik layar status datanya tetap sama, jadi tugas lama yang dulu "ditolak" otomatis tampil sebagai "Ditunda".
- Notifikasi HP (Web Push) **hanya bisa diterima dari portal yang diakses lewat HTTPS** (server online), bukan dari alamat lokal di laptop pengembang. Setiap kali pengiriman notifikasi gagal, sistem mencatatnya di log server beserta alasannya, sehingga masalah (mis. pengaturan sertifikat SSL server) mudah dilacak. Setelah pembaruan, pengguna cukup membuka ulang portal sekali agar komponen notifikasi versi terbaru aktif di perangkatnya.

---

## 9. Yang Perlu Dilengkapi Manual Sebelum Dikirim ke Atasan

- [ ] Nama resmi sistem untuk dokumen (kalau berbeda dari "Portal Internship Syifa Global Group")
- [ ] Tanggal/versi dokumentasi & nama penyusun
- [ ] Jabatan penanda tangan di sertifikat (saat ini tertulis "Head of Department") sudah sesuai atau belum
- [ ] Screenshot alur (opsional, untuk mempercantik dokumen presentasi)
- [ ] Target pembaca dokumen (tim internal / laporan magang / SOP resmi) — akan memengaruhi tingkat formalitas bahasa yang dipakai
