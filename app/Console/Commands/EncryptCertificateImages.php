<?php

namespace App\Console\Commands;

use App\Support\CertificateSignature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;

class EncryptCertificateImages extends Command
{
    protected $signature = 'certificate:encrypt-images
        {--delete-original : hapus file PNG asli setelah berhasil dienkripsi}';

    protected $description = 'Enkripsi stempel & tanda tangan sertifikat (storage/app/private/*.png -> *.png.enc, pakai APP_KEY)';

    public function handle(): int
    {
        $encrypted = 0;

        foreach (CertificateSignature::FILES as $file) {
            $plain = storage_path("app/private/{$file}");

            if (! is_file($plain)) {
                $this->warn("Lewati {$file}: file tidak ditemukan di storage/app/private.");

                continue;
            }

            file_put_contents($plain . '.enc', Crypt::encryptString(file_get_contents($plain)));
            $this->info("{$file} -> {$file}.enc");
            $encrypted++;

            if ($this->option('delete-original')) {
                unlink($plain);
                $this->line("  {$file} asli dihapus.");
            }
        }

        if ($encrypted === 0) {
            $this->error('Tidak ada file yang dienkripsi.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->warn('File .enc hanya bisa dibuka dengan APP_KEY yang sama — simpan APP_KEY dan cadangan PNG asli di tempat aman.');

        return self::SUCCESS;
    }
}
