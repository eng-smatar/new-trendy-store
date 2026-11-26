<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    // Deliberately empty: `migrate --seed` must work, and no account may ship with a known password.
    // Create administrators with `php artisan make:filament-user`; settings have defaults in config/store.php.
    public function run(): void {}
}
