<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            LookupSeeder::class,
            EvaluationFormSeeder::class,
            DemoUsersSeeder::class,
        ]);
    }
}
