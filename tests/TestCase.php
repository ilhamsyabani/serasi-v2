<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Pengaman: test (RefreshDatabase → migrate:fresh) hanya boleh berjalan di
     * SQLite in-memory. Jika konfigurasi bocor ke DB asli (mis. karena
     * `php artisan config:cache`), hentikan SEBELUM ada tabel yang di-drop.
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        $config = $app['config'];
        $koneksi = $config->get('database.default');
        $database = $config->get("database.connections.{$koneksi}.database");

        if ($app->environment() !== 'testing' || $database !== ':memory:') {
            throw new RuntimeException(sprintf(
                'Test dibatalkan demi keamanan data: env="%s", koneksi="%s", database="%s". '
                .'Test hanya boleh memakai SQLite :memory:. Jalankan `php artisan config:clear` lalu ulangi.',
                $app->environment(), $koneksi, $database
            ));
        }

        return $app;
    }
}
