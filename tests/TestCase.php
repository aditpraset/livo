<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Database yang boleh dipakai test. Test memakai RefreshDatabase, yang
     * MENGHAPUS & migrasi ulang seluruh tabel — kalau koneksinya menunjuk ke
     * database kerja/produksi, seluruh data hilang.
     *
     * Ini pernah terjadi: `bootstrap/cache/config.php` (config cache) membuat
     * pengaturan sqlite di phpunit.xml terabaikan, sehingga test berjalan di
     * database asli. Guard di bawah membuat kejadian itu mustahil terulang:
     * test langsung dihentikan sebelum satu pun migrasi dijalankan.
     */
    private const ALLOWED_TEST_DATABASES = [':memory:', 'testing', 'livo_testing'];

    public function createApplication()
    {
        $app = parent::createApplication();

        $connection = $app['config']->get('database.default');
        $database   = $app['config']->get("database.connections.{$connection}.database");

        if (!in_array($database, self::ALLOWED_TEST_DATABASES, true)) {
            throw new \RuntimeException(
                "STOP: test diarahkan ke database '{$database}' (koneksi '{$connection}'), " .
                'bukan database test. RefreshDatabase akan MENGHAPUS seluruh datanya. ' .
                'Jalankan `php artisan config:clear` (config cache membuat phpunit.xml terabaikan), ' .
                'lalu ulangi. Database yang diizinkan: ' . implode(', ', self::ALLOWED_TEST_DATABASES) . '.'
            );
        }

        return $app;
    }
}
