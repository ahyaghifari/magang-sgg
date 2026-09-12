# Brief: Dokumentasi Sistem — Portal Magang Syifa Global Group

> File ini adalah **brief/bahan mentah**, bukan dokumentasi final. Tujuannya: ditempel ke ChatGPT (atau alat lain) sebagai konteks untuk *menyusun* dokumentasi sistem yang rapi (bisa dalam bentuk .docx, laporan, atau wiki). Semua fakta di sini diambil langsung dari kode per 2026-09-12.

---

## 1. Instruksi untuk ChatGPT

Susun **Dokumentasi Sistem** dari aplikasi berikut untuk keperluan internal (laporan ke atasan / dokumentasi magang). Gunakan bahasa Indonesia formal. Struktur dokumen yang diinginkan:

1. Pendahuluan (latar belakang, tujuan sistem)
2. Gambaran Umum Sistem
3. Arsitektur & Teknologi
4. Peran Pengguna (Role) & Hak Akses
5. Modul & Fitur (per modul, jelaskan alur kerja/business process-nya)
6. Struktur Data / Entitas Utama (boleh dalam bentuk tabel deskriptif, tidak perlu skema SQL mentah)
7. Alur Autentikasi (login lokal, SSO, approval akun)
8. Integrasi Eksternal
9. Penutup / Rencana Pengembangan Selanjutnya (opsional)

Buat naratif, bukan sekadar list — jelaskan **mengapa** fitur itu ada dan **bagaimana alurnya** dari sudut pandang pengguna. Boleh tambahkan diagram alur sederhana (teks/flowchart) untuk proses approval dan alur presensi.

---

## 2. Gambaran Umum

**Nama sistem:** Portal Magang — Syifa Global Group (nama kerja internal; `.env` masih default "Laravel", belum di-branding di level `APP_NAME`, tapi UI sudah full branding Syifa Global Group).

**Tujuan:** Portal digital untuk mengelola siklus peserta magang (intern) di perusahaan — mulai dari pendaftaran, penempatan unit kerja, jurnal harian, presensi, pengajuan izin, penugasan, hingga penilaian oleh pembimbing.

**Jenis aplikasi:** Web app internal (bukan publik), diakses oleh pegawai/pembimbing dan peserta magang.

---

## 3. Teknologi

| Layer | Teknologi |
|---|---|
| Backend framework | Laravel (PHP) |
| Frontend interaktif | Livewire (full-page components, tanpa SPA/JS framework terpisah) |
| Admin panel | Filament v5 (untuk pengelolaan data master oleh admin) |
| Styling | Tailwind CSS v4 + CSS custom (banyak inline style secara sengaja karena isu build/JIT), Font Awesome 6, dark mode manual |
| Build tool | Vite |
| Autentikasi | Login lokal (email/password) + opsional SSO via **Keycloak** (OpenID Connect, Laravel Socialite) |
| Database | Relasional (migrations Laravel standar); ada **koneksi database kedua read-only** ke sistem HRIS eksternal untuk data absensi mentah |
| PWA | Ada elemen PWA (bottom nav mobile, app bar) — portal didesain mobile-first juga |

---

## 4. Peran Pengguna (Role)

Kolom `users.role` (enum): **Admin**, **User**, **Intern**, **Pembimbing**. Selain itu ada Shield roles lama (`super_admin`, `admin`, `peserta`) yang sedang ditransisi ke enum ini secara paralel.

| Role | Deskripsi & akses |
|---|---|
| **Admin / Super Admin** | Akses penuh ke panel `/admin` (Filament): kelola User, Intern, Institusi, Company, Unit, jadwal, presensi, jurnal, dsb. Tidak pernah diblokir status approval. Login diarahkan langsung ke `/admin`, bukan portal. |
| **Pembimbing** | Mentor/pengawas magang. Akses ke portal (bukan admin panel): melihat feed aktivitas jurnal seluruh intern (`/kegiatan`), memberi rating bintang 1–5 per jurnal, melihat & menyetujui presensi intern (`/presensi-intern`), mengelola tugas untuk intern (`/tugas-intern`), menyetujui/menolak pengajuan izin (`/izin-intern`). |
| **Intern (peserta magang)** | Pengguna utama portal: mengisi jurnal harian, presensi, mengajukan izin, melihat & menyelesaikan tugas. |
| **User (umum)** | Role default/legacy, jarang dipakai secara aktif di alur bisnis saat ini. |

Beberapa admin/pembimbing juga bisa punya data `Intern` sendiri (misal Direktur yang juga mengisi jurnal) — ada toggle "Lihat sebagai Intern" di sidebar (`canToggleIntern()`).

---

## 5. Modul & Fitur

### 5.1 Registrasi & Approval Akun
- Registrasi mandiri (`/register`): nama, email, institusi asal, jenis kelamin, password.
- Akun baru **berstatus pending** (`approved_at = null`) — tidak bisa login sampai disetujui admin lewat panel Filament (aksi "Setujui"/"Tolak" per baris, atau bulk approve).
- Setelah register otomatis dibuatkan data `Intern` terkait, tapi **belum punya unit kerja** — admin yang menentukan penempatan unit belakangan.

### 5.2 Autentikasi
- Login lokal email + password.
- Opsional Single Sign-On via **Keycloak** (OIDC) — dicocokkan berdasarkan email, tidak auto-provisioning (user harus sudah terdaftar). Bisa dimatikan dengan mengosongkan env `KEYCLOAK_*`.
- Single logout: jika login via SSO, logout dari portal juga mengakhiri sesi Keycloak.

### 5.3 Jurnal Harian (Journals)
- Intern mengisi jurnal kegiatan harian (isi kegiatan + lampiran foto/PDF).
- Upload foto dikompresi otomatis di sisi client (resize + re-encode) sebelum diupload, ada juga tombol "Kamera" untuk langsung ambil foto dari HP.
- Pembimbing melihat feed semua jurnal semua intern (dipaginasi per hari, bukan per baris — 1 hari tidak pernah terpecah ke halaman berbeda), bisa difilter per peserta / kata kunci.
- Pembimbing memberi **rating bintang 1–5** per jurnal (satu rating per pembimbing per jurnal); intern melihat rata-rata rating dari semua pembimbing sebagai bentuk penilaian performa.

### 5.4 Presensi (Attendance)
- Ada dua sumber: presensi manual dari portal, dan **sinkronisasi dari alat scan HRIS eksternal** (`access_logs`) via koneksi database read-only kedua.
- Pipeline: baca `access_logs` dari DB HRIS → cocokkan `employee_id` ke NIP intern → pasangkan jadi check-in (scan pertama) & check-out (scan terakhir) per hari → hitung telat/pulang cepat berdasarkan **jadwal fixed per perusahaan** (`CompanyFixedSchedule`) → simpan sebagai `AttendanceRecord`.
- Tidak mendukung shift fleksibel/lembur/shift lintas tengah malam — sengaja disederhanakan (lihat `kalkulator-jadwal-fixed-sederhana.md`).
- Pembimbing bisa melihat & memantau presensi intern binaannya (`/presensi-intern`).

### 5.5 Izin (Leave Requests)
- Intern mengajukan izin (tanpa tipe "cuti" — dihapus dari enum tipe izin).
- Pembimbing/admin menyetujui atau menolak pengajuan (`/izin-intern`).

### 5.6 Tugas (Tasks)
- Pembimbing memberi tugas ke intern (`/tugas-intern`).
- Intern melihat & menyelesaikan tugas (`/tugas`), termasuk upload **foto bukti penyelesaian tugas**.

### 5.7 Komentar (Comments)
- Fitur komentar generik (polymorphic — `commentable`) yang bisa dipasang ke entitas lain (jurnal, tugas, dll.) sebagai diskusi/feedback. Trait `HasCommentThread` dipakai di komponen Livewire yang butuh fitur ini.

### 5.8 Struktur Organisasi
- `Company` (perusahaan) → punya banyak `Unit` (mis. IT, Humas) → `Unit` punya banyak `User`/`Intern`.
- Terpisah dari `Institution` (asal sekolah/kampus intern, bukan unit kerja penempatan).
- Admin mengelola Company & Unit lewat Filament; penempatan unit intern dilakukan manual oleh admin (tidak ada auto-assign saat registrasi).

### 5.9 Admin Panel (Filament, `/admin`)
Kelola data master: Users, Interns, Institutions, Companies, Units, Journals, Attendance Records, Access Scan Logs, Company Fixed Schedules. Sudah dibrandingkan visual senada dengan portal (warna navy/hijau dari logo Syifa Global Group), bukan lagi tema default Filament.

---

## 6. Alur Approval (ringkas untuk diagram)

```
Registrasi mandiri → status "pending" (belum bisa login)
        │
        ▼
 Admin review di /admin (Filament)
        │
   ┌────┴────┐
   ▼         ▼
Setujui    Tolak (row dihapus)
   │
   ▼
Intern bisa login → admin assign Unit kerja
        │
        ▼
Intern aktif mengisi Jurnal / Presensi / Izin / Tugas
        │
        ▼
Pembimbing memantau, menilai (rating jurnal), approve/reject izin
```

---

## 7. Integrasi Eksternal

1. **Keycloak SSO (OpenID Connect)** — opsional, login terpusat lintas aplikasi perusahaan berbasis email sebagai penghubung akun.
2. **Database HRIS (read-only, koneksi kedua)** — sumber data mentah alat absensi (`access_logs`, mis. dari alat Hikvision), dibaca berkala (cron/manual) untuk dihitung jadi rekap presensi lokal.

---

## 8. Catatan Tambahan (opsional dimasukkan ke dokumentasi atau cukup jadi catatan internal)

- Desain UI mengikuti design system internal (`DESIGN.md`) — palet warna navy/hijau/magenta dari logo perusahaan, dark mode penuh di portal (tidak berlaku di panel admin), navigasi mobile pakai bottom nav bukan hamburger drawer, setiap halaman wajib punya layout mobile & desktop terpisah.
- Sistem role sedang masa transisi dari Filament Shield roles ke enum `UserRole` sendiri — keduanya berjalan paralel sementara ini (kode lama dicek dua-duanya).
- `APP_NAME` di `.env` belum diubah dari default "Laravel" — perlu disesuaikan jika ingin nama resmi muncul di title/email, ini murni konfigurasi bukan fitur.

---

## 9. Yang perlu dilengkapi manual sebelum dikirim ke atasan

- [ ] Nama resmi sistem (jika beda dari "Portal Magang — Syifa Global Group")
- [ ] Tanggal/versi dokumentasi & siapa yang menyusun
- [ ] Screenshot alur (opsional, untuk mempercantik dokumen)
- [ ] Target pembaca dokumen (internal tim / laporan magang / SOP resmi) — akan memengaruhi tingkat formalitas bahasa
