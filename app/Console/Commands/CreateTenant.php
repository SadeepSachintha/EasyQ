<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class CreateTenant extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-tenant {id}';

    protected $description = 'Create a new tenant with a subdomain';

    public function handle()
    {
        $id = $this->argument('id');
        $this->info("Creating tenant: {$id}...");

        if (Tenant::where('id', $id)->exists()) {
            $this->error('Tenant already exists!');

            return self::FAILURE;
        }

        $tenant = Tenant::create(['id' => $id]);
        $domainName = "{$id}.localhost";
        $tenant->domains()->create(['domain' => $domainName]);

        $this->info("Tenant {$id} created successfully!");
        $this->info("Domain: http://{$domainName}:8000");

        return self::SUCCESS;
    }
}
