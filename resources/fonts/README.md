# Font untuk editor & PDF sertifikat

File TTF statis (Regular, Bold, Italic, BoldItalic) dari Google Fonts, dipakai oleh
editor sertifikat (panel gaya → Font) dan didaftarkan lewat `@font-face` di
`resources/views/certificates/pkl.blade.php` supaya DomPDF bisa merender font yang sama.

Daftar font yang boleh dipilih ada di `App\Support\CertificateContent::FONTS` — kalau
menambah font baru, taruh 4 file TTF-nya di folder `resources/fonts/{slug}/` dengan nama
`Regular.ttf`, `Bold.ttf`, `Italic.ttf`, `BoldItalic.ttf`, lalu daftarkan di konstanta itu.

Semua font di sini berlisensi SIL Open Font License 1.1 (bebas dipakai & didistribusikan):
Plus Jakarta Sans, Poppins, Roboto, Montserrat, Playfair Display, Merriweather.
