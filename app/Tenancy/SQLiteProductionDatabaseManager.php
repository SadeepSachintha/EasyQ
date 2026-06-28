<?php

namespace App\Tenancy;

use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\TenantDatabaseManagers\SQLiteDatabaseManager as BaseManager;

class SQLiteProductionDatabaseManager extends BaseManager
{
    protected function getPath(string $databaseName): string
    {
        // In production/NativePHP, store under the internal database directory in AppData!
        $internalPath = config('nativephp-internal.database_path');
        if ($internalPath) {
            $dir = dirname($internalPath);
            if (! file_exists($dir)) {
                mkdir($dir, 0755, true);
            }

            return "{$dir}/{$databaseName}.sqlite";
        }

        // Default local development fallback
        return database_path("{$databaseName}.sqlite");
    }

    public function createDatabase(TenantWithDatabase $tenant): bool
    {
        try {
            $path = $this->getPath($tenant->database()->getName());

            return file_put_contents($path, '') !== false;
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function deleteDatabase(TenantWithDatabase $tenant): bool
    {
        try {
            return unlink($this->getPath($tenant->database()->getName()));
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function databaseExists(string $name): bool
    {
        return file_exists($this->getPath($name));
    }

    public function makeConnectionConfig(array $baseConfig, string $databaseName): array
    {
        $baseConfig['database'] = $this->getPath($databaseName);

        return $baseConfig;
    }
}
