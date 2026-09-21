# Brief: Dokumentasi Sistem — Portal Internship Syifa Global Group

> File ini adalah **bahan presentasi/dokumentasi**, ditulis dengan bahasa yang mudah dipahami orang non-teknis (atasan, pembimbing, atau audiens presentasi). Istilah teknis (nama file, class, route) sengaja diminimalkan di badan teks — kalau perlu contoh lebih rinci untuk keperluan teknis, bisa ditambahkan sebagai lampiran terpisah. Semua fakta di sini diambil langsung dari kode per 2026-09-17.

---

## 1. Instruksi untuk ChatGPT (kalau file ini ditempel ke alat lain untuk dirapikan)

Susun **Dokumentasi Sistem** dari aplikasi berikut untuk keperluan presentasi/laporan internal. Gunakan bahasa Indonesia formal namun tetap mengalir dan mudah dipahami — hindari istilah teknis kecuali benar-benar perlu. Struktur dokumen yang diinginkan:

1. Pendahuluan (latar belakang, tujuan sistem)
2. Gambaran Umum Sistem
3. Arsitektur & Teknologi (ringkas, tidak perlu detail implementasi)
4. Peran Pengguna & Hak Akses
5. Modul & Fitur (per modul, jelaskan alur kerja dari sudut pandang pengguna)
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
| Login | Login email/password, plus opsional **Single Sign-On (SSO)** lewat Keycloak | Karyawan yang sudah punya akun terpusat perusahaan bisa langsung login tanpa akun terpisah. |
| Desain | Mobile-first, mendukung mode gelap di sisi portal | Portal dirancang supaya nyaman dipakai dari HP, karena peserta magang & pembimbing sering mengaksesnya di lapangan. |

---

## 4. Peran Pengguna & Hak Akses

Setiap akun di sistem punya satu dari lima peran berikut:

| Peran | Siapa | Bisa apa saja |
|---|---|---|
| **Admin** | Pengelola sistem (biasanya staf HR/IT) | Akses penuh ke panel admin: mengelola akun, data peserta magang, institusi, perusahaan/unit, jadwal kerja, sampai menyetujui pendaftar baru. Admin tidak pernah terblokir status persetujuan apa pun — selalu bisa masuk. |
| **Pembimbing** | Mentor/pengawas magang di unit kerja | Memantau seluruh kegiatan harian peserta magang binaannya, memberi penilaian bintang pada jurnal, memberi & memantau tugas, menyetujui atau menolak pengajuan izin, serta memantau presensi. |
| **Mentor** | Pendamping/pengawas magang, sebutan jabatan yang berbeda dari Pembimbing | Hak aksesnya saat ini **sama persis** dengan Pembimbing (menilai jurnal, mengelola tugas, memutuskan izin, memantau presensi) — dibedakan hanya dari sisi penyebutan/jabatan, bukan dari sisi kewenangan di sistem. |
| **Pimpinan** | Direktur/pimpinan perusahaan yang ingin memantau tanpa ikut mengelola | Punya dashboard ringkasan tersendiri berisi jurnal, presensi, dan pengajuan izin seluruh peserta magang — sifatnya **hanya memantau**, tidak bisa menyetujui/menolak izin, menilai jurnal, maupun mengelola tugas. |
| **Intern** (peserta magang) | Peserta magang aktif | Pengguna utama sehari-hari: mengisi jurnal kegiatan harian, mencatat presensi, mengajukan izin/sakit, serta melihat dan menyelesaikan tugas dari pembimbing. |

Catatan: beberapa admin, pembimbing, atau mentor (mis. seorang Direktur yang juga aktif mengisi jurnal) bisa punya data peserta magang sendiri — ada saklar "Lihat sebagai Intern" untuk berpindah sudut pandang tanpa perlu dua akun terpisah.

---

## 5. Modul & Fitur

### 5.1 Pendaftaran & Persetujuan Akun
Calon peserta mendaftar sendiri lewat halaman pendaftaran (nama, email, asal institusi/sekolah, jenis kelamin, kata sandi). Akun baru **belum aktif** — tidak bisa login sampai disetujui oleh admin di panel admin (admin bisa menyetujui atau menolak satu per satu, atau sekaligus banyak akun). Setelah disetujui, admin baru menentukan unit kerja penempatannya secara manual.

### 5.2 Login & Autentikasi
Login pakai email dan kata sandi seperti biasa. Sebagai alternatif, tersedia **Single Sign-On (SSO)** lewat Keycloak — pengguna yang sudah punya akun terpusat perusahaan bisa langsung masuk tanpa mendaftar ulang, dicocokkan berdasarkan alamat email. Kalau pengguna login lewat SSO lalu logout dari portal, sesi di sistem SSO ikut berakhir juga (logout menyeluruh, bukan cuma dari portal ini).

### 5.3 Jurnal Harian
Peserta magang mengisi catatan kegiatan setiap hari, boleh dilengkapi lampiran foto atau dokumen PDF. Foto yang diunggah otomatis dikecilkan ukurannya di perangkat pengguna sebelum dikirim (supaya hemat kuota & penyimpanan), dan ada tombol untuk langsung memotret dari kamera HP. Pembimbing melihat semua jurnal dari semua peserta magang dalam satu tampilan (dikelompokkan per hari, difilter per peserta atau kata kunci), lalu bisa memberi **penilaian bintang 1–5** pada tiap jurnal sebagai bentuk evaluasi kinerja — nilai rata-rata dari semua pembimbing yang menilai ditampilkan kembali ke peserta. Foto lampiran bisa diperbesar (zoom) langsung di halaman yang sama tanpa membuka tab baru.

### 5.4 Presensi
Data kehadiran berasal dari dua sumber: presensi manual dari portal, dan sinkronisasi otomatis dari alat sidik jari perusahaan. Sistem membaca data mentah dari alat tersebut, mencocokkan ke identitas peserta magang, lalu menghitung jam masuk/pulang, keterlambatan, dan pulang cepat berdasarkan jadwal kerja yang berlaku di masing-masing perusahaan. Pembimbing bisa memantau presensi seluruh peserta binaannya dari satu halaman. (Catatan: sistem ini sengaja disederhanakan untuk jadwal kerja tetap — belum mendukung shift fleksibel atau lembur lintas hari.)

### 5.5 Pengajuan Izin
Peserta magang mengajukan izin atau sakit lewat portal, lalu pembimbing (atau admin) meninjau untuk menyetujui atau menolak.

- **Notifikasi otomatis**: begitu ada pengajuan baru, pembimbing mendapat notifikasi langsung di browser/HP-nya (mirip notifikasi aplikasi chat) — dan sebaliknya, peserta magang diberi tahu begitu izinnya diputuskan. Notifikasi ini perlu diaktifkan sekali oleh masing-masing pengguna (tombol "Aktifkan Notifikasi").
- Sebagai cadangan kalau notifikasi tidak aktif, menu "Izin Intern" di sisi pembimbing selalu menampilkan angka jumlah pengajuan yang masih menunggu, jadi tidak akan terlewat.
- Pengajuan yang sudah diputuskan (disetujui/ditolak) bisa dihapus oleh pembimbing untuk merapikan data lama; pengajuan yang masih menunggu tidak bisa dihapus sampai diputuskan dulu.
- *Pengembangan lanjutan yang masih dipertimbangkan:* mengirim notifikasi lewat email atau WhatsApp — belum dikerjakan karena butuh pengaturan tambahan (server pengirim email yang sesungguhnya, atau berlangganan layanan pihak ketiga untuk WhatsApp).

### 5.6 Pemberian & Pengelolaan Tugas
Pembimbing bisa memberi tugas langsung ke peserta magang lewat portal (lengkap dengan tenggat waktu — tanggal dan jam), atau peserta magang mencatat sendiri tugas yang disampaikan secara lisan. Saat tugas selesai, peserta mengunggah foto sebagai bukti pengerjaan (foto ini bisa langsung dijadikan catatan jurnal harian juga, sekali klik).

- **Kalau peserta belum bisa mengerjakan** (ada urusan lain, dsb.), tersedia tombol **Tolak** dengan alasan singkat — pembimbing langsung mendapat notifikasi beserta alasannya, dan bisa menyesuaikan tugas tersebut (ubah tenggat/keterangan) atau membukanya kembali.
- Peserta magang hanya bisa mengubah status tugasnya sendiri (mulai, tandai selesai, atau tolak) — tidak bisa mengedit maupun menghapus tugas, supaya kontrol isi tugas tetap di tangan pembimbing.
- Pembimbing punya kendali penuh: bisa menandai selesai, membuka kembali, mengedit, atau menghapus tugas kapan saja.
- Sama seperti izin, menu "Tugas Intern" di sisi pembimbing menampilkan angka peringatan kalau ada tugas yang ditolak dan belum ditindaklanjuti.

### 5.7 Diskusi/Komentar
Setiap jurnal atau tugas punya kolom diskusi sendiri — pembimbing dan peserta bisa saling berkomentar langsung di situ, jadi tidak perlu pindah ke aplikasi chat terpisah untuk membahas satu kegiatan/tugas tertentu.

### 5.8 Struktur Organisasi
Data perusahaan disusun berjenjang: satu **Perusahaan** punya beberapa **Unit kerja** (mis. IT, Humas), dan setiap peserta magang atau pegawai ditempatkan di salah satu unit tersebut. Ini terpisah dari data **Institusi asal** (sekolah/kampus peserta magang) — dua hal yang berbeda: satu soal dari mana peserta berasal, satu lagi soal di mana dia ditempatkan bekerja. Admin yang mengatur struktur ini dan menentukan penempatan setiap peserta secara manual.

Khusus akun Pembimbing/Mentor/Pimpinan, admin juga bisa mengaitkan akun tersebut langsung ke satu **Perusahaan** (terpisah dari Unit) — berguna kalau pembimbing/mentor/pimpinan tersebut mengawasi lintas beberapa unit dalam satu perusahaan yang sama, supaya tetap jelas perusahaan mana yang menjadi induknya. Kolom ini sifatnya **hanya info profil**, bukan pembatas akses data — tidak mengubah data peserta magang mana saja yang bisa dilihat/dikelola oleh akun tersebut.

### 5.9 Panel Admin
Tempat admin mengelola seluruh data master sistem: akun pengguna, data peserta magang, institusi asal, data perusahaan & unit kerja, jurnal, presensi, dan jadwal kerja.

- **Tampilan** panel admin sudah disesuaikan supaya senada dengan portal peserta (warna, bentuk kartu, tabel) — bukan lagi tampilan bawaan generik.
- **Ringkasan di halaman utama**: kartu statistik jumlah peserta magang aktif, jurnal yang masuk hari ini, ringkasan presensi hari ini, dan jumlah akun yang menunggu persetujuan — masing-masing bisa diklik untuk langsung menuju datanya. Ditambah grafik tren jumlah jurnal 7 hari terakhir.
- Data peserta magang kini punya kolom **Nama Panggilan** (opsional), untuk membantu pembimbing mengenali peserta dengan nama yang lebih akrab dibanding nama resmi — saat ini baru tersimpan di data admin, rencananya akan ditampilkan juga di halaman-halaman pembimbing.

### 5.10 Dashboard Pimpinan
Khusus akun berperan **Pimpinan**, sistem menyediakan satu halaman ringkasan tersendiri (bukan halaman "Beranda" biasa) yang menampilkan jurnal, presensi (default: hari ini), dan pengajuan izin dari **seluruh** peserta magang sekaligus — masing-masing bisa difilter dan dijelajahi terpisah. Halaman ini murni untuk memantau: tidak ada tombol menyetujui/menolak izin, menilai jurnal, atau mengelola tugas di sini — keputusan/pengelolaan tetap jadi wewenang Pembimbing/Mentor. Pimpinan juga sengaja tidak diikutsertakan dalam notifikasi pengajuan izin baru, karena bukan pihak yang memutuskan.

### 5.11 Navigasi & Pengalaman Pengguna di HP
Karena banyak dipakai lewat HP, menu utama (sidebar) dirancang sebagai laci yang bisa dibuka lewat tombol ataupun dengan **geser jari dari tepi kiri layar** — kebiasaan yang sudah familiar dari aplikasi-aplikasi populer. Menu "Beranda" (halaman ringkasan untuk peserta magang) ditempatkan sebagai menu pertama yang paling mudah dijangkau.

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
Peserta bisa login → admin menentukan unit kerja penempatan
        │
        ▼
Peserta aktif: mengisi jurnal, presensi, mengajukan izin, mengerjakan tugas
        │
        ▼
Pembimbing memantau, menilai jurnal, memutuskan izin & tugas
```

---

## 7. Integrasi dengan Sistem Lain

1. **Single Sign-On (Keycloak)** — opsional, memungkinkan login terpusat lintas aplikasi perusahaan berbasis email yang sama, tanpa perlu akun terpisah per aplikasi.
2. **Sistem Absensi Perusahaan (HRIS)** — sistem ini membaca data mentah alat sidik jari perusahaan secara berkala (hanya membaca, tidak mengubah data di sistem sumber), lalu mengolahnya jadi rekap kehadiran yang tampil di portal.

---

## 8. Catatan Tambahan (untuk internal, opsional dimasukkan ke dokumentasi)

- Sistem peran pengguna sempat punya peran keempat bernama **"User"** (peran generik/cadangan) — per 2026-09-16 peran ini **dihapus** karena tidak pernah benar-benar dipakai dalam alur bisnis.
- Per 2026-09-17, ditambahkan dua peran baru: **Mentor** (hak akses identik dengan Pembimbing, hanya beda sebutan) dan **Pimpinan** (akses pemantauan ringkas lewat dashboard tersendiri, tanpa wewenang mengelola). Total peran saat ini: Admin, Pembimbing, Mentor, Pimpinan, Intern.
- Nama brand yang tampil ke pengguna diubah dari "Magang" menjadi **"Internship"** (judul halaman, login, sidebar) per 2026-09-16 — istilah "magang" di kalimat-kalimat deskriptif lain (mis. "peserta magang") sengaja tetap dipakai karena lebih wajar dalam Bahasa Indonesia.
- Sebagian sistem peran lama (dari paket pihak ketiga yang sebelumnya dipakai) masih berjalan berdampingan sementara proses migrasi ke sistem peran baru selesai sepenuhnya — tidak berdampak ke pengguna, murni urusan teknis di belakang layar.
- Nama aplikasi secara resmi (`APP_NAME`) di pengaturan server belum disesuaikan dari nilai bawaan — ini murni pengaturan teknis, tidak memengaruhi tampilan yang dilihat pengguna.

---

## 9. Yang Perlu Dilengkapi Manual Sebelum Dikirim ke Atasan

- [ ] Nama resmi sistem untuk dokumen (kalau berbeda dari "Portal Internship Syifa Global Group")
- [ ] Tanggal/versi dokumentasi & nama penyusun
- [ ] Screenshot alur (opsional, untuk mempercantik dokumen presentasi)
- [ ] Target pembaca dokumen (tim internal / laporan magang / SOP resmi) — akan memengaruhi tingkat formalitas bahasa yang dipakai
