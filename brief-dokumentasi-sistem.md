                # Brief: Dokumentasi Sistem — Portal Internship Syifa Global Group

                > File ini adalah **bahan presentasi/dokumentasi**, ditulis dengan bahasa yang mudah dipahami orang non-teknis (atasan, pembimbing, atau audiens presentasi). Istilah teknis (nama file, class, route) sengaja diminimalkan di badan teks — kalau perlu contoh lebih rinci untuk keperluan teknis, bisa ditambahkan sebagai lampiran terpisah. Semua fakta di sini diambil langsung dari kode per 2026-10-09.

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
                | Dokumen PDF | DomPDF, plus html2canvas & jsPDF di editor sertifikat | Membuat sertifikat magang & form penilaian resmi langsung dari sistem dalam bentuk PDF siap cetak. Editor sertifikat juga bisa membuat PDF langsung di browser dari tampilan yang sedang diedit. |
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
                Peserta magang mengisi catatan kegiatan setiap hari, boleh dilengkapi lampiran foto atau dokumen PDF (kolom teks kegiatan boleh dikosongkan kalau lampiran sudah cukup menjelaskan). Foto yang diunggah otomatis dikecilkan ukurannya di perangkat pengguna sebelum dikirim (supaya hemat kuota & penyimpanan), dan ada tombol untuk langsung memotret dari kamera HP. Pembimbing/Mentor melihat jurnal peserta dalam satu tampilan (halaman "Kegiatan"). Secara bawaan halaman ini menampilkan **hari ini dan satu minggu sebelumnya**, dengan **hari ini selalu di paling atas** sebagai fokus utama — kalau hari ini belum ada jurnal masuk, bagian "Hari Ini" tetap tampil dengan keterangan kosong. Jurnal dikelompokkan per hari dengan judul nama hari saja ("Hari Ini", "Selasa", "Rabu", …) yang masing-masing punya **warna berbeda** supaya mudah dibedakan sekilas. Tampilan bisa difilter per tanggal, peserta, atau kata kunci. Pembimbing/Mentor lalu bisa memberi **penilaian bintang 1–5** pada jurnal peserta binaannya sendiri sebagai bentuk evaluasi kinerja — nilai rata-rata dari semua pembimbing yang menilai ditampilkan kembali ke peserta. Foto lampiran bisa diperbesar (zoom) langsung di halaman yang sama tanpa membuka tab baru (lihat 5.14 untuk cara zoom di HP).

                ### 5.4 Presensi
                Data kehadiran berasal dari dua sumber: presensi manual dari portal, dan sinkronisasi otomatis dari alat sidik jari perusahaan. Sistem membaca data mentah dari alat tersebut, mencocokkan ke identitas peserta magang, lalu menghitung jam masuk/pulang, keterlambatan, dan pulang cepat berdasarkan jadwal kerja yang berlaku di masing-masing perusahaan. Pembimbing bisa memantau presensi seluruh peserta binaannya dari satu halaman. Halaman yang sama juga menampilkan daftar **izin/sakit yang sudah disetujui**, terpisah dari rekap sidik jari, supaya hari tidak masuk karena izin tidak disangka alfa. Izin hanya tampil **pada tanggal izin itu berlaku** — secara bawaan yang terlihat hanya izin/sakit **hari ini**, dan kalau pembimbing memilih rentang tanggal tertentu, yang tampil adalah izin pada tanggal-tanggal tersebut (bukan seluruh riwayat izin sekaligus). Untuk peserta yang memakai shift, perhitungan mengikuti jam shift pada tanggal itu (lihat 5.16).

                Sinkronisasi dengan alat sidik jari berjalan otomatis setiap beberapa detik dan dibuat **tahan terhadap data yang datang terlambat**. Alat sidik jari sering mengirim data tap ke sistem absensi perusahaan dengan jeda atau sekaligus per paket (misalnya saat jaringan alat sempat putus), sehingga tap pukul 07:55 bisa baru tiba pukul 08:10. Sistem kini selalu membaca ulang data dua jam terakhir di setiap sinkronisasi, ditambah **sinkronisasi ulang penuh untuk hari kemarin dan hari ini setiap jam** sebagai jaring pengaman — jadi jam masuk maupun jam pulang tidak lagi terlewat. Pembacaan ulang ini aman: data yang sama tidak akan tercatat dobel. Peserta magang yang datanya belum lengkap (belum punya unit/perusahaan) dicatat di log sistem supaya bisa segera dilengkapi admin.

                ### 5.5 Pengajuan Izin
                Peserta magang mengajukan izin atau sakit lewat portal, lalu Pembimbing-nya (atau admin) meninjau untuk menyetujui atau menolak. Izin bisa untuk **sehari penuh** atau hanya **beberapa jam** (mis. pulang cepat, ada urusan 2 jam) dengan mengisi jam mulai & jam selesai.

                - **Notifikasi otomatis**: begitu ada pengajuan baru, Pembimbing mendapat notifikasi langsung di HP/komputernya (lengkap dengan tanggal izin dan alasannya) — dan sebaliknya, peserta magang diberi tahu begitu izinnya diputuskan. Selengkapnya soal notifikasi di bagian 5.15.
                - Sebagai cadangan kalau notifikasi tidak aktif, menu "Izin Intern" di sisi pembimbing selalu menampilkan angka jumlah pengajuan yang masih menunggu, jadi tidak akan terlewat.
                - Pengajuan yang sudah diputuskan (disetujui/ditolak) bisa dihapus oleh pembimbing untuk merapikan data lama; pengajuan yang masih menunggu tidak bisa dihapus sampai diputuskan dulu. Setiap penghapusan (izin, tugas, komentar) selalu meminta konfirmasi dulu supaya tidak terhapus karena salah pencet.
                - *Pengembangan lanjutan yang masih dipertimbangkan:* mengirim notifikasi lewat email atau WhatsApp — belum dikerjakan karena butuh pengaturan tambahan (server pengirim email yang sesungguhnya, atau berlangganan layanan pihak ketiga untuk WhatsApp).

                ### 5.6 Pemberian & Pengelolaan Tugas
                Pembimbing bisa memberi tugas langsung ke peserta magang lewat portal (lengkap dengan tenggat waktu — tanggal dan jam), atau peserta magang mencatat sendiri tugas yang disampaikan secara lisan. Saat tugas selesai, peserta mengunggah **satu atau beberapa foto** sebagai bukti pengerjaan (foto-foto ini bisa langsung dijadikan catatan jurnal harian juga, sekali klik).

                - **Satu tugas untuk banyak peserta sekaligus**: kalau tugasnya sama, pembimbing/mentor cukup mengisi form sekali lalu memilih beberapa peserta. Pilihan peserta dibagi dua kotak terpisah — **"Peserta yang Kamu Bimbing/Mentori"** dan **"Peserta Lain (Lintas Pembimbing)"** — masing-masing dengan pilihan dropdown sendiri; peserta yang sudah dipilih tampil sebagai label yang bisa dibatalkan dengan sekali klik, dan ada tombol "Pilih semua" untuk seluruh binaan sendiri. Walau dikirim sekaligus, **setiap peserta tetap mendapat tugasnya masing-masing**, sehingga status selesai/ditunda, foto bukti, dan diskusinya tidak tercampur antar-peserta.
                - **Tugas berbeda untuk tiap peserta (opsional)**: kalau tugas yang dikirim sekaligus ternyata perlu pembagian kerja yang berbeda, form punya bagian lipat *"Tugas berbeda untuk tiap peserta"* (muncul bila peserta yang dipilih dua orang atau lebih). Di situ tiap peserta punya kotak **"Tugas khusus {nama}"** sendiri — misalnya keterangan umum "Rekap inventaris lab", lalu kotak si A "bagian komputer" dan si B "bagian printer". Kotak yang dikosongkan berarti peserta itu memakai keterangan umum; kotak yang diisi membuat keterangan peserta itu berisi keterangan umum di atas dan tugas khususnya di bawah. Ada tombol bantu "Salin keterangan umum ke semua kotak". Saat dua peserta atau lebih dipilih, kolom keterangan berganti label menjadi **"Keterangan umum (untuk semua peserta)"** supaya jelas bedanya dengan kotak tugas khusus.
                - **Judul tugas boleh dikosongkan**: kalau judul tidak diisi, sistem mengambilnya otomatis dari baris pertama keterangan peserta tersebut, atau "Tugas dari {nama pemberi}" bila keterangan juga kosong.
                - **Form tidak tertutup karena salah klik**: jendela Beri Tugas dan Edit Tugas tidak lagi tertutup saat area di luar kotak tersentuh tanpa sengaja — hanya lewat tombol ✕, Batal, atau tombol Esc — jadi isian tidak hilang.
                - **Tugas yang sama tampil dalam satu kotak (sisi pembimbing/mentor)**: di halaman "Tugas Intern", tugas yang diberikan sekaligus ke beberapa peserta tidak lagi berulang satu per satu, melainkan digabung dalam **satu kotak**. Judul, keterangan, tenggat, dan pemberi tugas tampil sekali di atas, disertai ringkasan seperti *"3 peserta · 1/3 selesai"*. Di bawahnya ada daftar peserta — masing-masing dengan status, tombol tandai selesai/buka lagi, alasan menunda, foto bukti, dan tombol **Diskusi** miliknya sendiri. Pembimbing/mentor bisa **mengedit tugas untuk semua peserta sekaligus**, menghapus seluruh kotak, atau mengeluarkan satu peserta saja. Kalau isi tugas tiap peserta berbeda (karena tugas khusus atau karena satu peserta diedit sendiri), kotak itu berjudul **"Tugas berbeda untuk tiap peserta"** dengan judul aslinya tampil kecil di bawah, bagian yang berbeda tampil di baris masing-masing peserta, dan tombol "Edit untuk semua" diganti tombol **Edit per peserta**. Di **sisi peserta magang tidak ada perubahan** — setiap peserta tetap melihat tugasnya sendiri seperti biasa. Tugas lama yang dibuat sebelum fitur ini juga ikut tergabung otomatis bila isinya sama persis.
                - **Tombol cepat "Bimbingan saya" / "Dampingan saya"**: di atas filter halaman Tugas Intern ada dua tombol besar — **"Semua intern"** dan **"Bimbingan saya"** (untuk Pembimbing) atau **"Dampingan saya"** (untuk Mentor) — lengkap dengan jumlah pesertanya. Sekali klik, daftar tugas, angka ringkasan, dan pilihan peserta di filter langsung menyempit ke peserta yang memang ditugaskan kepadanya.
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
                - **Ringkasan di halaman utama**: kartu statistik jumlah peserta magang aktif, jurnal yang masuk hari ini, ringkasan presensi hari ini, dan jumlah akun yang menunggu persetujuan — masing-masing bisa diklik untuk langsung menuju datanya. Ditambah grafik tren jumlah jurnal 7 hari terakhir. (Kartu bawaan berisi versi & tautan dokumentasi Filament sudah dihapus supaya dashboard lebih bersih.)
                - Data peserta magang punya kolom **Nama Panggilan** (opsional), untuk membantu pembimbing mengenali peserta dengan nama yang lebih akrab. Nama ini sudah tampil di halaman Intern dan Sertifikat di sisi pembimbing.
                - Saat admin membuka detail sebuah jurnal, foto lampiran kegiatan bisa diklik untuk dibuka dalam ukuran penuh di tab baru.
                - **Shift**: daftar shift (Pagi/Siang/Malam) per perusahaan beserta jam, toleransi, dan jendela absen — bagian dari fitur Jadwal Shift (lihat 5.16). Intern mana yang memakai shift dipilih langsung di data peserta magang lewat pilihan **"Memakai jadwal shift?" Ya / Tidak** (juga tampil sebagai kolom & filter di daftar peserta).
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

                - Setiap kriteria dinilai dengan klik **bintang 1–5** (boleh setengah bintang, mis. 4,5), sama seperti menilai jurnal. Setiap kartu kriteria menampilkan nama kriteria, penjelasan singkatnya, deretan bintang, angka nilai, dan tombol hapus — atau keterangan "belum dinilai" bila belum diisi. Penilai juga bisa menambahkan catatan bebas.
                - **Nilai akhir** dihitung otomatis dari rata-rata 10 kriteria, lalu diberi predikat: **Excellent** (≥ 4,5), **Good** (≥ 3,5), **Fair** (≥ 2,5), **Below Average** (≥ 1,5), dan **Poor** (di bawahnya). Sistem juga mencatat siapa yang menilai dan kapan.
                - Mentor bisa melihat sertifikat semua peserta, tetapi hanya bisa menilai peserta yang dimentorinya.
                - Sistem langsung membuat **dokumen PDF ukuran A4 landscape yang bisa dicetak bolak-balik**. Halaman depan berisi **Sertifikat PKL** (logo perusahaan, judul, nama peserta, asal sekolah, keterangan program, periode, predikat, tempat & tanggal, penanda tangan, dan nomor sertifikat), dan halaman belakang berisi **form penilaian resmi** lengkap dengan nilai dan predikatnya. Dokumen bisa dilihat di browser atau diunduh.
                - **Nama peserta di sertifikat selalu berhuruf depan kapital** tiap kata (mis. "al fatih" tercetak "Al Fatih"), seperti penulisan nama resmi — huruf lain dibiarkan seperti aslinya, dan data nama di sistem sendiri tidak diubah.
                - Peserta magang bisa melihat & mengunduh sertifikatnya sendiri dari halaman Beranda **setelah tanggal selesai magangnya tiba**. Pembimbing/Mentor dan admin bisa mengaksesnya kapan saja untuk pratinjau atau cetak.

                **Editor Sertifikat.** Sebelum sertifikat dicetak atau diunduh, isinya bisa dirapikan langsung lewat halaman editor (tombol **Buka Editor** di halaman Sertifikat sisi pembimbing/mentor dan di panel admin):

                - Editor menampilkan pratinjau **dua halaman A4 landscape** yang tampilannya sama dengan sertifikat asli. **Setiap teks bisa diubah** — nama peserta, asal sekolah, kalimat kegiatan, periode, predikat, nama & jabatan penanda tangan, nomor sertifikat, nama dan penjelasan tiap kriteria, nilai, catatan penilai, total nilai, dan rating. Kalau nilai per kriteria diubah, total nilai dan predikat di sertifikat ikut dihitung ulang otomatis.
                - **Desain halaman depan bisa diatur bebas**: klik sekali sebuah teks untuk memilihnya (muncul garis putus-putus biru), lalu teks itu bisa **digeser ke posisi mana pun** dengan mouse atau jari, dan muncul **panel gaya** untuk mengganti **jenis huruf** (Plus Jakarta Sans, Poppins, Roboto, Montserrat, Playfair Display, Merriweather, atau huruf bawaan), **ukuran**, **tebal**, **miring**, dan **warna**. Klik dua kali (atau tombol "Edit teks") untuk mengetik ulang isinya. Saat digeser, muncul garis bantu ketika teks pas di tengah halaman, dan teks tidak bisa keluar dari batas kertas. Tombol "Reset gaya elemen ini" mengembalikan posisi dan gaya satu teks ke bawaan.
                - **Posisi, jenis huruf, dan warna yang diatur di editor ikut dipakai PDF resmi** yang diunduh peserta, sehingga hasil cetak mengikuti desain di editor. (Hasil PDF sangat mirip dengan tampilan editor; perbedaan beberapa piksel atau letak pindah baris pada kalimat panjang bisa terjadi karena cara mesin PDF menggambar huruf sedikit berbeda dari browser.)
                - Setelah selesai, tekan **Simpan** (atau Ctrl+S). Editan **tersimpan permanen** dan otomatis dipakai juga oleh **PDF resmi** yang diunduh peserta magang, pembimbing, maupun admin. Toolbar menampilkan status ("Belum disimpan", "Tersimpan", atau alasan gagal) dan catatan **siapa yang terakhir mengedit dan kapan**. Kalau halaman ditutup padahal ada editan yang belum disimpan, sistem memberi peringatan dulu.
                - Tombol **Reset ke Data Asli** menghapus semua editan sehingga sertifikat kembali mengikuti data di sistem.
                - Yang disimpan hanya bagian yang benar-benar diubah. Bagian lain tetap mengikuti data terbaru — misalnya kalau penilaian diperbarui setelah nama peserta dirapikan, nilai barunya tetap tampil di sertifikat.
                - **Editan hanya berlaku untuk tampilan sertifikat.** Mengubah angka di editor **tidak** mengubah data penilaian asli (bintang di halaman Sertifikat tetap seperti semula) — hal ini juga dicantumkan sebagai pengingat di editor.
                - Editor juga menyediakan tombol **Unduh PDF** (dibuat langsung di browser) dan **Print**; toolbar dan tanda sorotan teks tidak ikut tercetak.
                - **Hak akses mengikuti aturan sertifikat yang sudah ada**: yang boleh mengedit dan menyimpan hanya Admin, Pembimbing untuk binaannya, dan Mentor untuk mentee-nya sendiri. Mentor yang membuka sertifikat intern lain, serta peserta magang (setelah tanggal selesai magang), hanya melihat versi final dalam mode baca-saja. Pimpinan tidak mengakses sertifikat.

                ### 5.13 Profil & Personalisasi Beranda Peserta
                Peserta magang bisa mengganti **foto profil** sendiri dan memilih **warna kartu Beranda** sesuai selera. Foto dipotong bulat langsung di HP sebelum dikirim, jadi foto aslinya tidak ikut diunggah. Warna teks di kartu otomatis menyesuaikan supaya tetap terbaca, baik di warna terang maupun gelap.

                Foto profil ini juga muncul di samping nama peserta di halaman-halaman Pembimbing/Mentor (Kegiatan, Tugas, Presensi, Izin, Intern, dan Sertifikat), sehingga pembimbing lebih mudah mengenali wajah peserta binaannya. Foto bisa diklik untuk diperbesar. Kalau peserta belum mengunggah foto, yang tampil adalah inisial namanya. Foto yang sama juga tampil di kartu profil pada sidebar (bagian bawah menu), di kolom diskusi, dan sebagai gambar pada notifikasi HP yang dikirim oleh peserta tersebut — selalu dalam bentuk bulat penuh.

                ### 5.14 Navigasi & Pengalaman Pengguna di HP
                Karena banyak dipakai lewat HP, menu utama (sidebar) dirancang sebagai laci yang bisa dibuka lewat tombol ataupun dengan **geser jari dari tepi kiri layar** — kebiasaan yang sudah familiar dari aplikasi-aplikasi populer. Menu "Beranda" (halaman ringkasan untuk peserta magang) ditempatkan sebagai menu pertama yang paling mudah dijangkau. Menu yang tampil menyesuaikan peran: Pembimbing/Mentor melihat Kegiatan, Tugas, Presensi, Izin (khusus Pembimbing), Intern, dan Sertifikat, sedangkan peserta magang melihat Beranda, Jurnal Harian, Tugas, dan Izin. Ikon tab browser (favicon) memakai lambang Syifa Global Group.

                Foto yang diklik (lampiran jurnal, bukti tugas, foto profil) terbuka dalam jendela besar yang **bisa diperbesar dan diperkecil dengan jari**: cubit dua jari untuk zoom, ketuk dua kali untuk langsung memperbesar titik tertentu atau kembali normal, dan geser satu jari untuk melihat bagian lain saat foto sedang diperbesar. Di laptop, zoom memakai scroll mouse dan klik-tahan untuk menggeser; di semua perangkat juga tersedia tombol perbesar/perkecil di layar. Tampilan kotak tugas di HP juga sudah dirapikan supaya judul, tenggat, dan label tidak saling bertumpuk di layar sempit.

                Portal juga bisa **dipasang ke layar utama HP** layaknya aplikasi (menu "Tambahkan ke layar utama" di Chrome, atau "Tambah ke Layar Utama" di Safari iPhone), dengan nama **Magang SGG** dan ikon lambang Syifa. Setelah dipasang, portal terbuka tanpa bilah alamat browser — dan khusus iPhone, cara inilah yang membuat notifikasi bisa diterima.

                Supaya portal terasa lebih hidup, latar belakang setiap halaman portal (bukan panel admin) dihiasi **ikon-ikon bertema magang yang bergerak perlahan**: buku, laptop, HP, pena, papan tugas, kalender, sidik jari (presensi), dan topi wisuda. **Warnanya mengikuti peran** pengguna yang sedang masuk, sehingga sekilas terlihat sedang berada di "ruang" siapa: hijau-biru-ungu untuk peserta magang, oranye-merah-merah muda untuk Mentor, nuansa biru untuk Pembimbing (dan Admin), serta ungu-emas-gelap untuk Pimpinan. Ikon dibuat samar dan tidak bisa diklik, jadi tidak mengganggu isi halaman; animasinya tidak mengulang dari awal saat berpindah halaman. Di HP sebagian ikon disembunyikan agar layar tidak ramai, dan bila perangkat diatur untuk **mengurangi gerakan** (pengaturan aksesibilitas), ikon tetap tampil tetapi diam. Bilah atas halaman juga dibuat sedikit tembus pandang dan buram (efek kaca) sehingga latar tetap terlihat halus di baliknya.

                ### 5.15 Notifikasi di HP
                Supaya informasi penting tidak terlewat, portal mengirim notifikasi yang muncul di **panel notifikasi HP** (atau pojok layar komputer), sama seperti notifikasi WhatsApp — walaupun portal sedang tidak dibuka. Kejadian yang memicu notifikasi:

                | Kejadian | Siapa yang diberi tahu | Isi notifikasi |
                |---|---|---|
                | Tugas baru diberikan | Peserta yang diberi tugas | Nama pemberi tugas, judul & keterangan tugas, tenggat |
                | Tugas ditunda peserta | Pemberi tugas (pembimbing atau mentor) | Nama peserta, judul tugas, alasan menunda |
                | Komentar baru di diskusi | Semua pihak di diskusi itu (kecuali penulisnya) | Nama penulis, jurnal/tugas yang dibahas, isi komentar |
                | Pengajuan izin/sakit baru | Pembimbing yang ditugaskan ke peserta tersebut | Nama peserta, tanggal izin, alasan |
                | Izin diputuskan | Peserta pengaju | Hasil keputusan, siapa yang memutuskan, catatan |
                | Pengajuan perubahan shift baru | Mentor peserta tersebut (satu notifikasi per kali kirim) | Nama peserta, tanggal & shift lama → baru (maks. 3, sisanya "+N lainnya"), alasan |
                | Pengajuan perubahan shift diputuskan | Peserta pengaju | Hasil keputusan, nama mentor, catatan |

                Ringkasnya per peran: **peserta magang** menerima notifikasi tugas baru, komentar, hasil izin, dan hasil pengajuan perubahan shift; **mentor** menerima notifikasi tugas yang ditunda (untuk tugas yang ia berikan), komentar, dan pengajuan perubahan shift dari peserta dampingannya (pembimbing tidak); **pembimbing** menerima semua yang diterima mentor ditambah pengajuan izin baru. Mentor sengaja tidak menerima notifikasi izin karena keputusan izin adalah wewenang Pembimbing, dan Pimpinan tidak menerima notifikasi karena perannya hanya memantau.

                Contoh tampilan notifikasi di HP:

                ```
                Tugas baru dari Labib
                Rekap inventaris laboratorium
                Kerjakan sampai selesai lalu unggah fotonya
                Tenggat: Selasa, 29 Sep 2026 · 16:30
                                                [Lihat Tugas]   [Nanti]
                ```

                - **Tampilan notifikasi** dibuat bersih dan mudah dibaca sekilas: judul singkat, isi dipecah beberapa baris dengan label yang jelas (mis. *Tugas:*, *Alasan:*, *Tanggal:*, *Tenggat:*) tanpa emoji, gambar di sisi kanan berupa **foto profil pengirim** bila ada (logo tidak diulang karena sudah tampil sebagai ikon aplikasi), serta tombol aksi seperti **Lihat Tugas / Balas / Tinjau** dan **Nanti**. Mengetuk notifikasi langsung membuka halaman yang relevan. (Warna dan huruf notifikasi mengikuti bawaan masing-masing HP — ini batasan standar notifikasi, bukan dari sistem.)
                - **Berlaku untuk Android maupun iPhone.** Di Android cukup lewat Chrome. Di iPhone (iOS 16.4 ke atas), portal harus dipasang dulu ke layar utama lalu dibuka dari ikon tersebut — kalau pengguna iPhone menekan tombol aktifkan dari Safari biasa, sistem menampilkan petunjuk langkah-langkahnya.
                - **Cukup diaktifkan sekali per perangkat** lewat tombol "Aktifkan Notifikasi" — di menu **Tugas** untuk peserta magang, dan di menu **Tugas Intern** untuk pembimbing/mentor — lalu memilih "Izinkan". Setelah itu tidak perlu menekan tombol lagi, termasuk setelah logout-login atau ganti akun: setiap kali portal dibuka, perangkat otomatis ditautkan ke akun yang sedang login dan tombolnya tersembunyi sendiri. Satu akun bisa menerima notifikasi di beberapa perangkat sekaligus, asalkan masing-masing sudah diaktifkan. Sebaliknya, satu perangkat hanya mengirim notifikasi ke **satu akun sekaligus** — yaitu akun yang terakhir membuka portal di perangkat itu.
                - **Tahan gangguan**: setiap kali portal dibuka, sistem otomatis memperbarui "alamat" notifikasi perangkat di server, sehingga notifikasi tetap sampai walaupun browser memperbarui datanya. Notifikasi dikirim dengan prioritas tinggi dan disimpan hingga 24 jam, jadi HP yang sedang mati atau tanpa sinyal tetap menerimanya begitu tersambung lagi.
                - Hal di luar kendali sistem yang bisa membuat notifikasi terlambat/tidak berbunyi: mode Senyap/Jangan Ganggu, izin notifikasi Chrome dimatikan di pengaturan HP, atau penghemat baterai yang agresif (umum di beberapa merek HP Android).

                ### 5.16 Jadwal Shift
                Untuk unit yang bekerja bergiliran (pagi, siang, dan malam), sistem punya fitur **Jadwal Shift**: **Mentor** mengisi shift peserta dampingannya per tanggal, dan presensi hari itu dihitung berdasarkan jam shift tersebut — bukan jam kerja tetap perusahaan. Peserta yang tidak memakai shift tidak terpengaruh sama sekali.

                - **Shift** di panel admin (khusus super admin, mengikuti aturan akses panel yang sudah ada). Untuk tiap perusahaan, admin membuat shift dengan **jenis Pagi, Siang, atau Malam** (nama lengkap, satu jenis per perusahaan). Saat jenis dipilih, jam kerjanya **terisi otomatis** — Pagi 08:00–14:00, Siang 14:00–20:00, Malam 20:00–08:00 — dan masih bisa disesuaikan, begitu juga lama istirahat, toleransi telat & pulang cepat, serta "jendela absen". Bila jam pulang lebih awal dari jam masuk (seperti Malam), sistem otomatis menganggapnya **pulang keesokan hari**.
                - Di data **peserta magang** (bukan di menu Shift), admin memilih **"Memakai jadwal shift?" Ya / Tidak** untuk tiap peserta. **Ya** = jadwalnya diisi Mentor per tanggal dan menu "Jadwal Shift" muncul di portal peserta; **Tidak** = mengikuti jam kerja biasa perusahaan dan menu itu tidak muncul. Peserta tidak lagi dikaitkan ke satu master shift tertentu, karena Mentor bisa memilih Pagi, Siang, Malam, atau Libur per tanggal.
                - **Halaman Jadwal Shift peserta**: kalender bulanan yang nyaman di HP. Peserta **tidak bisa mengubah jadwalnya langsung**; bila berhalangan, ia **mengajukan perubahan** untuk hari ini sampai 30 hari ke depan (lihat "Pengajuan perubahan shift" di bawah).
                - **Halaman Jadwal Shift Intern** (menu "Jadwal Shift" di sisi Pembimbing, Mentor, dan Admin): pilih peserta lalu lihat kalendernya. **Hanya Mentor** dari peserta itu yang bisa mengubah: ketuk beberapa tanggal sekaligus (termasuk yang sudah lewat), lalu pilih salah satu cara di panel bawah layar:
                  - **Satu Shift untuk Semua**: satu shift, **Libur**, atau **Kosongkan** untuk semua tanggal terpilih.
                  - **Atur Per Tanggal**: tiap tanggal diberi pilihan sendiri (Pagi, Siang, Malam, atau Libur, dengan jamnya tertulis di bawah label), lalu **Simpan** sekaligus. Ada tombol **Isi semua dengan…**, preset rotasi (**Pagi → Siang → Malam**, **Pagi, Pagi, Siang, Siang, Libur**, **5 Pagi, 2 Libur**), dan **Reset**; preset hanya mengisi pilihan, tiap baris tetap bisa diubah. Baris yang belum dipilih dilewati, dan setelah simpan muncul ringkasan "X tersimpan, Z dilewati".

                  Supaya cepat, **nama hari** di atas kalender bisa diketuk untuk memilih semua hari itu dalam sebulan (mis. semua Senin), dan tombol kecil di kiri tiap baris memilih **satu minggu** sekaligus; ketuk lagi untuk melepas. Pilih cepat yang sama juga ada di halaman Jadwal Shift peserta (tanggal terkunci otomatis dilewati). Pembimbing dan Admin hanya bisa melihat.
                - **Pimpinan** tidak punya halaman terpisah: kalender jadwal shift (baca-saja) tampil langsung sebagai bagian **Dashboard Pimpinan**.
                - **Hak akses jadwal:**

                | Peran | Melihat jadwal shift | Mengubah jadwal shift |
                |---|---|---|
                | Peserta magang | Miliknya sendiri | Tidak bisa langsung; hanya **mengajukan** perubahan (hari ini s.d. 30 hari ke depan), berlaku setelah disetujui mentor |
                | Pembimbing | Peserta binaannya | Tidak bisa (hanya melihat) |
                | Mentor | Semua peserta | **Satu-satunya** yang mengubah, hanya untuk peserta dampingannya, kapan saja (termasuk tanggal lampau); juga **menyetujui/menolak** pengajuan perubahan dari peserta dampingannya |
                | Admin | Semua peserta | Tidak bisa (hanya melihat) |
                | Pimpinan | Semua peserta | Tidak bisa (hanya memantau) |

                - Satu peserta + satu tanggal = satu isian: **shift tertentu** atau **Libur**. Urutan penentuan jadwal presensi: isian shift → Libur (tidak dihitung, tidak menjadi alfa) → jadwal kerja tetap perusahaan bila tanggal itu belum diisi. Aturan ini dipakai di semua jalur presensi (sinkronisasi sidik jari tiap beberapa detik, sinkron ulang per jam, dan hitung ulang).
                - Setiap perubahan mencatat **siapa yang terakhir mengubah dan kapan** (terlihat saat menahan/mengarahkan kursor ke tanggal di kalender). Mentor selalu bisa memilih **Pagi**, **Siang**, dan **Malam** kapan saja; bila perusahaan peserta belum punya master shift jenis itu, sistem membuatnya otomatis dengan jam bawaan (Pagi 08:00–14:00, Siang 14:00–20:00, Malam 20:00–08:00) yang nanti bisa disesuaikan admin di menu Shift.
                - **Shift Malam (20:00–08:00)** dicatat sebagai satu hari kerja pada **tanggal shift dimulai**. Tap masuk malam ini dan tap pulang besok pagi digabung menjadi satu rekap. Telat, pulang cepat, dan lama kerja dihitung lintas tengah malam (mis. masuk 19:55 dan pulang 08:05 = 12 jam 10 menit). Tap besok pagi hanya dianggap tap pulang bila masih dalam jendela tap pulang shift Malam. Bila besoknya diisi shift lain (mis. Siang), tap yang lebih dekat ke jam masuk Siang tetap dihitung untuk Siang.
                - **Koreksi tanggal yang sudah lewat** otomatis menghitung ulang presensi tanggal itu (telat & pulang cepat mengikuti shift baru). Bila tanggal itu diubah menjadi Libur, presensi yang sudah tercatat tidak dihapus.
                - **Rekap presensi** (halaman Presensi peserta, Presensi Intern pembimbing/mentor, Dashboard Pimpinan, dan panel admin) menampilkan nama shift per hari, mis. "Pagi (08:00–14:00)" atau "Libur". Hari tanpa isian shift tampil seperti biasa.
                - Master shift yang sudah dipakai di jadwal peserta **tidak bisa dihapus**, supaya riwayat jadwal & presensi tetap utuh.
                - **Fitur izin tidak diubah sama sekali** — jadwal shift tidak memengaruhi izin, dan sebaliknya. Hal ini juga dijaga oleh tes otomatis.

                **Pengajuan perubahan shift (oleh peserta)**
                - Hanya peserta yang **memakai shift** (admin memilih "Ya") yang bisa mengajukan — syarat yang sama dengan munculnya menu Jadwal Shift.
                - Peserta mengetuk satu atau beberapa tanggal di kalendernya, lalu menekan **Ajukan Perubahan**. Tiap tanggal terpilih tampil sebagai baris dengan **shift saat ini** dan pilihan **Pagi | Siang | Malam | Libur** (ada tombol bantu **Isi semua dengan…**), ditambah **satu kolom Alasan** untuk semua tanggal (wajib, misalnya berhalangan karena urusan keluarga). Bila alasannya berbeda-beda, peserta bisa menyalakan **"Alasan berbeda per tanggal"**: tiap baris mendapat kolom alasan sendiri, dan tanggal yang alasannya dikosongkan otomatis memakai **alasan umum**. Tombol **Kirim Pengajuan (N tanggal)** baru aktif bila ada pilihan dan setiap tanggal terpilih sudah punya alasan (alasan sendiri atau alasan umum).
                - **Semua pilihan menjadi pengajuan** — termasuk tanggal yang masih kosong. Jadwal baru berubah setelah **Mentor** peserta itu menyetujuinya. Tanggal yang sedang diajukan ditandai ikon jam pasir di kalender. Satu tanggal hanya bisa punya satu pengajuan yang menunggu.
                - Yang bisa diajukan hanya **hari ini sampai 30 hari ke depan**. **Tanggal yang sudah lewat** dan **tanggal yang sudah ada tap sidik jarinya** terkunci untuk peserta, tetapi Mentor tetap bisa mengoreksinya seperti biasa. Tanggal yang terkunci, yang sudah punya pengajuan menunggu, atau yang pilihannya sama dengan jadwal saat ini **dilewati**; setelah kirim muncul ringkasan "X diajukan, Z dilewati".
                - Bila shift yang diajukan belum punya master shift di perusahaan peserta (misalnya Malam), master shift dibuat otomatis dengan jam bawaan **saat Mentor menyetujui** — peserta sendiri tidak pernah membuat master shift.
                - Peserta melihat daftar pengajuannya beserta statusnya (**Menunggu, Disetujui, Ditolak, Dibatalkan**) di bawah kalender, dan bisa **membatalkan** pengajuan yang masih menunggu.
                - Yang memutuskan **hanya Mentor dari peserta tersebut**. Pengajuan yang menunggu muncul di bagian atas halaman Jadwal Shift Intern milik mentor, lengkap dengan alasan, tombol **Setujui / Tolak**, dan kolom catatan opsional. Pembimbing, Admin, dan Pimpinan tidak memutuskan. Peserta yang belum punya mentor **belum bisa mengajukan** perubahan dan mendapat pesan yang jelas untuk menghubungi pembimbing.
                - **Notifikasi HP:** satu kali kirim pengajuan = **satu notifikasi gabungan** ke **Mentor**, misalnya "5 pengajuan perubahan shift dari {nama}", berisi maksimal 3 tanggal (shift lama → shift baru) lalu "+2 lainnya", dan alasannya — bila alasan tiap tanggal berbeda, alasannya ikut tertulis di baris masing-masing tanggal. Keputusan dikirim ke **peserta** (hasil, nama mentor, catatan). Pembimbing dan Pimpinan tidak menerima notifikasi ini.

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
                (Opsional) Pembimbing/Mentor/Admin merapikan teks sertifikat di Editor Sertifikat → Simpan
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
                - Pengiriman notifikasi membutuhkan sepasang **kunci VAPID** (semacam "stempel resmi" portal yang membuktikan ke server notifikasi Google/Apple bahwa notifikasi benar berasal dari portal ini). Per 2026-09-28 kunci ini sudah dipasang di server online dan notifikasi tugas baru sudah terbukti sampai ke HP. Kunci privatnya bersifat rahasia (hanya disimpan di pengaturan server) dan sebaiknya **tidak diganti-ganti** — mengganti kunci membuat semua perangkat harus mengaktifkan ulang notifikasinya.
                - Per 2026-09-28 ditambahkan **Editor Sertifikat** dengan penyimpanan permanen. Editan disimpan di tabel baru khusus sertifikat (satu baris per peserta, berisi hanya bagian yang diubah beserta siapa dan kapan terakhir mengedit); data penilaian asli tidak pernah ikut diubah. Server online perlu menjalankan *migrate* satu kali untuk membuat tabel ini — kalau lupa, sertifikat dan PDF tetap tampil dengan data asli (tidak error), hanya fitur Simpan yang belum bisa dipakai.
                - Per 2026-09-29, halaman depan PDF resmi dibangun ulang supaya **mengikuti editor sepenuhnya** (posisi, jenis huruf, ukuran, dan warna tiap teks), dan ukuran kertas PDF menjadi **A4 landscape** (sebelumnya 280×196 mm). Asal sekolah, predikat, dan tanggal kini juga tercetak di halaman depan PDF. Yang masih hanya tampil di editor: subjudul dan catatan penilai di halaman belakang. Jenis huruf disimpan sebagai file di dalam aplikasi (sekitar 5 MB, lisensi bebas pakai) supaya mesin PDF bisa mencetaknya; PDF hanya menyertakan huruf yang benar-benar dipakai sehingga ukuran filenya tetap kecil (sekitar 110 KB).
                - Per 2026-09-29, sinkronisasi presensi diperbaiki: tap sidik jari yang datang terlambat ke sistem absensi perusahaan kini tetap terbaca (sebelumnya bisa terlewat selamanya sehingga jam masuk/pulang kosong), ditambah sinkronisasi ulang per jam sebagai jaring pengaman. Agar berjalan, server harus menjalankan penjadwal Laravel setiap menit. Data yang terlanjur bolong bisa diperbaiki dengan menjalankan sinkronisasi ulang untuk rentang tanggal tertentu.
                - Per 2026-09-29 sempat dicoba mengganti nama tampilan aplikasi menjadi "SGG Internship", namun dibatalkan — nama yang tampil tetap **"Internship Syifa Global Group"**.
                - Per 2026-10-03, fitur Jadwal Shift (5.16) menambah **tiga tabel baru** di database: master shift, daftar intern per shift, dan jadwal shift harian per peserta. Ketiganya hanya tabel baru (tidak mengubah data presensi, izin, atau data lain yang sudah ada); server online perlu menjalankan *migrate* satu kali saat kode terbaru dipasang. Panel admin — termasuk menu Shift — tetap hanya bisa dibuka akun super admin (email yang terdaftar di pengaturan server); jadwal harian peserta diisi Pembimbing/Mentor lewat portal (Admin biasa hanya melihat). Bila ada staf lain yang perlu mengelola menu Shift, emailnya cukup ditambahkan ke daftar super admin.
                - Per 2026-10-09, aturan peran Jadwal Shift diubah (5.16): **Mentor satu-satunya yang mengisi/mengubah jadwal** peserta dampingannya; **Pembimbing kini hanya melihat** (sebelumnya ikut bisa mengubah); **peserta tidak lagi mengisi jadwal langsung**, melainkan **mengajukan perubahan** yang disetujui Mentor-nya. Ditambah mode **Atur Per Tanggal** (shift berbeda per tanggal, disimpan/diajukan sekaligus) dan notifikasi pengajuan yang digabung satu per kali kirim. Ini menambah **satu tabel baru** untuk pengajuan perubahan shift (peserta, tanggal, shift lama, shift/jenis shift yang diajukan, alasan, status, pemutus, catatan, waktu). Hanya tabel baru, sehingga data lain tidak berubah; server online perlu menjalankan *migrate* satu kali.
                - Per 2026-10-09, pemilihan intern di form Master Shift (panel admin) **dihapus** dan diganti pilihan **"Memakai jadwal shift?" Ya / Tidak** di data peserta magang (kolom baru di tabel peserta). Saat *migrate*, hanya peserta yang sebelumnya memang dipilih admin di form Master Shift yang otomatis diisi **Ya**; peserta lain (termasuk yang sekadar punya isian jadwal, misalnya data uji) diisi **Tidak** dan bisa diubah admin kapan saja. Tabel lama pendaftaran intern per shift dibiarkan apa adanya (tidak dipakai lagi, datanya tidak dihapus).
                - Per 2026-10-09, jam shift bawaan menjadi **Pagi 08:00–14:00** dan **Siang 14:00–20:00**, dan ditambah **Malam 20:00–08:00** (lintas hari). Saat *migrate*, master shift yang masih memakai jam bawaan lama (Pagi 08:30–16:30, Siang 12:00–21:00) otomatis diganti ke jam baru; shift yang jamnya sudah diubah admin tidak disentuh dan perlu dicek manual di menu Shift. Rekap presensi lama tidak dihitung ulang.
                - Per 2026-10-09, portal mendapat **latar belakang animasi** berwarna sesuai peran (lihat 5.14). Murni perubahan tampilan — tidak ada perubahan database, sehingga tidak perlu *migrate*; cukup membangun ulang aset tampilan (*build*) saat kode terbaru dipasang. Akun super admin yang membuka portal mendapat warna latar peserta magang (hijau).
                - Per 2026-09-28, label hijau "PRAKTIK KERJA LAPANGAN" di atas judul sertifikat dihapus (di PDF maupun editor), dan jabatan penanda tangan kiri di halaman depan PDF kini bawaannya **"Head of Department"** (sebelumnya "Pembimbing Lapangan") — keduanya tetap bisa diubah lewat editor.
                - Per 2026-09-28, tampilan pembimbing/mentor menggabungkan tugas yang sama menjadi satu kotak. Untuk itu setiap tugas yang dikirim sekaligus kini diberi penanda kelompok di database; server online perlu menjalankan pembaruan struktur database (*migrate*) satu kali saat kode terbaru dipasang.

                ---

                ## 9. Yang Perlu Dilengkapi Manual Sebelum Dikirim ke Atasan

                - [ ] Nama resmi sistem untuk dokumen (kalau berbeda dari "Portal Internship Syifa Global Group")
                - [ ] Tanggal/versi dokumentasi & nama penyusun
                - [ ] Jabatan penanda tangan di sertifikat (saat ini bawaannya "Head of Department", bisa diubah per sertifikat lewat Editor Sertifikat) sudah sesuai atau belum
                - [ ] Screenshot alur (opsional, untuk mempercantik dokumen presentasi)
                - [ ] Target pembaca dokumen (tim internal / laporan magang / SOP resmi) — akan memengaruhi tingkat formalitas bahasa yang dipakai
