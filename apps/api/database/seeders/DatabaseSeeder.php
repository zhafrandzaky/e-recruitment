<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Delegates to DemoDataSeeder, which fills every entity with realistic
     * Indonesian demo data for local/staging manual testing. See
     * docs/ENVIRONMENT.md for credentials and behaviour (it refuses to run in
     * production and no-ops if demo data already exists).
     */
    public function run(): void
    {
        $this->call(DemoDataSeeder::class);
    }
}
