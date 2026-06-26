<?php

namespace Tests;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    protected $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Run central migrations once if they haven't been run
        if (! Schema::hasTable('tenants')) {
            Artisan::call('migrate');
        }

        // 2. Drop test tenant database if it exists only when the central tenant record is missing to prevent "already exists" errors
        if (! Tenant::where('id', 'test')->exists()) {
            try {
                DB::statement('DROP DATABASE IF EXISTS tenanttest');
            } catch (\Exception $e) {
                // Ignore
            }
        }

        // 3. Create the test tenant if it does not exist
        $this->tenant = Tenant::firstOrCreate(['id' => 'test']);

        if ($this->tenant->domains()->count() === 0) {
            $this->tenant->domains()->create(['domain' => 'test.localhost']);
        }

        // 4. Run tenant migrations to ensure the tenant database is fully migrated
        Artisan::call('tenants:migrate', ['--tenants' => ['test']]);

        // 5. Initialize tenancy (registers the 'tenant' connection and switches connection)
        if (! tenancy()->initialized) {
            tenancy()->initialize($this->tenant);
        }

        // 5. Start transactions manually on the connections
        DB::connection('mysql')->beginTransaction();
        DB::connection('tenant')->beginTransaction();

        // Force request Host header to route through the tenant domain middleware
        $this->defaultHeaders = [
            'Host' => 'test.localhost',
        ];
    }

    protected function tearDown(): void
    {
        // Rollback transactions manually
        try {
            DB::connection('tenant')->rollBack();
        } catch (\Exception $e) {
        }

        try {
            DB::connection('mysql')->rollBack();
        } catch (\Exception $e) {
        }

        if (tenancy()->initialized) {
            tenancy()->end();
        }

        parent::tearDown();
    }

    /**
     * Rewrite target request URLs to route to test.localhost tenant domain
     */
    protected function prepareUrlForRequest($url)
    {
        if (str_starts_with($url, '/')) {
            return 'http://test.localhost'.$url;
        }

        return str_replace('http://localhost', 'http://test.localhost', $url);
    }
}
