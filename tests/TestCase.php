<?php

namespace Tests;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $host = parse_url((string) config('app.url'), PHP_URL_HOST);
        if (is_string($host) && $host !== '') {
            $this->withServerVariables([
                'HTTP_HOST' => $host,
                'SERVER_NAME' => $host,
            ]);
        }
    }

    /**
     * SQLite testing DB file must exist before bootstrap when using a path (not :memory:).
     * `central` is aliased to the default connection in AppServiceProvider when both use the same sqlite file.
     */
    public function createApplication()
    {
        if (($GLOBALS['_ENV']['APP_ENV'] ?? $_ENV['APP_ENV'] ?? getenv('APP_ENV')) === 'testing') {
            $db = (string) ($_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE') ?? '');
            if ($db !== '' && $db !== ':memory:' && ! str_contains($db, 'mode=memory')) {
                $root = dirname(__DIR__);
                $path = str_starts_with($db, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:\\\\/', $db)
                    ? $db
                    : $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $db);
                if (! is_file($path)) {
                    touch($path);
                }
            }
        }

        $app = parent::createApplication();

        if ($app->environment('testing')) {
            $app['config']->set('tenancy.database.auto_create', false);

            $app['config']->set('database.connections.sqlite.busy_timeout', 60000);

            try {
                $app['db']->connection()->getPdo()->exec('PRAGMA busy_timeout=60000');
            } catch (\Throwable) {
            }
        }

        return $app;
    }

    /**
     * Create tenant SQLite file (if missing) and run tenant migrations. Use when tests skip tenancy DB jobs.
     */
    protected function provisionTenantSqliteAndMigrate(Tenant $tenant): void
    {
        $name = $tenant->database_name;
        if ($name === null || $name === '') {
            $name = config('tenancy.database.prefix', 'tenant_').$tenant->id.config('tenancy.database.suffix', '_db');
        }

        $path = database_path($name);
        if (! is_file($path)) {
            touch($path);
        }

        tenancy()->initialize($tenant);

        try {
            $this->artisan('migrate', [
                '--database' => 'tenant',
                '--path' => database_path('migrations/tenant'),
                '--realpath' => true,
            ]);
        } finally {
            tenancy()->end();
        }
    }

    /**
     * Remove leftover tenant SQLite files from disk (DatabaseMigrations + migrate:fresh does not delete them).
     */
    protected function deleteTenantSqliteFiles(): void
    {
        foreach (glob(database_path('tenant_*')) ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }
}
