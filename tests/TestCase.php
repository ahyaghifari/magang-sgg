<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /** Satu-satunya database yang boleh dipakai tes (lihat phpunit.xml). */
    private const TEST_DATABASE = 'db_laravel_filament_test';

    /**
     * Pengaman: dicek SEBELUM RefreshDatabase mengosongkan database — jangan sampai database
     * utama ikut kena kalau konfigurasi ter-cache (config:cache) atau env salah.
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        $config = $app['config'];
        $database = $config->get('database.connections.' . $config->get('database.default') . '.database');

        if ($database !== self::TEST_DATABASE) {
            throw new RuntimeException("Tes dihentikan: database aktif '{$database}', bukan '" . self::TEST_DATABASE . "'. Jalankan `php artisan config:clear` lalu ulangi.");
        }

        return $app;
    }
}
